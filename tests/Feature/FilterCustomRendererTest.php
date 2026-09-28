<?php

use ErickComp\LivewireDataTable\DataTable\Filter;
use Illuminate\View\ComponentAttributeBag;

it('renders custom renderer code with x-model and name attributes on select', function () {
    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'status',
            'name' => 'status',
            'input-type' => Filter::TYPE_SELECT,
            'label' => 'Status',
        ]),
        customRendererCode: '<select><option value="">All</option><option value="1">Active</option><option value="0">Inactive</option></select>',
    );

    $result = $filter->getCustomRendererCodeWithXModel('inputFilters');

    expect($result)->toContain('x-model')
        ->and($result)->toContain('name=');
});

it('extracts select options from custom renderer HTML', function () {
    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'status',
            'name' => 'status',
            'input-type' => Filter::TYPE_SELECT,
            'label' => 'Status',
        ]),
        customRendererCode: '<select><option value="">Choose</option><option value="1">Active</option><option value="0">Inactive</option></select>',
    );

    $filter->getCustomRendererCodeWithXModel('inputFilters');

    expect($filter->getSelectOptions())->toBe([
        '' => 'Choose',
        '1' => 'Active',
        '0' => 'Inactive',
    ]);
});

it('does not overwrite explicit options prop with extracted options', function () {
    $explicitOptions = ['a' => 'Alpha', 'b' => 'Beta'];

    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'status',
            'name' => 'status',
            'input-type' => Filter::TYPE_SELECT,
            'label' => 'Status',
            'options' => $explicitOptions,
        ]),
        customRendererCode: '<select><option value="1">One</option></select>',
    );

    $filter->getCustomRendererCodeWithXModel('inputFilters');

    expect($filter->getSelectOptions())->toBe($explicitOptions);
});

it('caches rendered custom code on second call', function () {
    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'status',
            'name' => 'status',
            'input-type' => Filter::TYPE_SELECT,
            'label' => 'Status',
        ]),
        customRendererCode: '<select><option value="1">Yes</option></select>',
    );

    $first = $filter->getCustomRendererCodeWithXModel('inputFilters');
    $second = $filter->getCustomRendererCodeWithXModel('inputFilters');

    expect($first)->toBe($second);
});

it('throws when select custom renderer has no select element', function () {
    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'status',
            'name' => 'status',
            'input-type' => Filter::TYPE_SELECT,
            'label' => 'Status',
        ]),
        customRendererCode: '<input type="text" />',
    );

    $filter->getCustomRendererCodeWithXModel('inputFilters');
})->throws(\LogicException::class);

it('binds x-model only to the input that does not declare one, without an index suffix', function () {
    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'cpf',
            'name' => 'cpf',
            'input-type' => Filter::TYPE_TEXT,
            'label' => 'CPF',
        ]),
        customRendererCode: '<div><input type="text" x-model="display"><input type="hidden"></div>',
    );

    $result = $filter->getCustomRendererCodeWithXModel('inputFilters');

    expect($result)->toContain('x-model="display"')
        ->and($result)->toContain("x-model=\"dtData()['inputFilters']['cpf']['cpf']\"")
        ->and($result)->not->toContain('.0');
});

it('throws when select custom renderer has no options and no explicit options prop', function () {
    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'status',
            'name' => 'status',
            'input-type' => Filter::TYPE_SELECT,
            'label' => 'Status',
        ]),
        customRendererCode: '<select></select>',
    );

    $filter->getCustomRendererCodeWithXModel('inputFilters');
})->throws(\LogicException::class);

it('inserts name attribute into input element', function () {
    $filter = new Filter(
        new ComponentAttributeBag([
            'data-field' => 'query',
            'name' => 'query',
            'input-type' => Filter::TYPE_TEXT,
            'label' => 'Query',
        ]),
        customRendererCode: '<input type="text" />',
    );

    $result = $filter->getCustomRendererCodeWithXModel('inputFilters');

    expect($result)->toContain('name=')
        ->and($result)->toContain('x-model=')
        ->and($result)->toContain('x-on:keydown.enter');
});

// --- Exact output of the DOM manipulations (name, x-on:keydown.enter and x-model) ---

function renderedFilterCustomCode(array $attributes, string $customRendererCode): string
{
    $filter = new Filter(new ComponentAttributeBag($attributes), customRendererCode: $customRendererCode);

    return $filter->getCustomRendererCodeWithXModel('inputFilters');
}

it('inserts name and x-model into a custom rendered select, keeping its options', function () {
    $filter = new Filter(
        new ComponentAttributeBag(['data-field' => 'status', 'input-type' => Filter::TYPE_SELECT]),
        customRendererCode: '<select class="form-control"><option value="">Todos</option><option value="1">Sim</option><option value="0">Não</option></select>',
    );

    expect($filter->getCustomRendererCodeWithXModel('inputFilters'))->toBe(
        '<select class="form-control" name="inputFilters[status][status]" x-model="dtData()[\'inputFilters\'][\'status\'][\'status\']">'
        . '<option value="">Todos</option><option value="1">Sim</option><option value="0">Não</option></select>'
    )
        ->and($filter->getSelectOptions())->toBe(['' => 'Todos', '1' => 'Sim', '0' => 'Não']);
});

it('extracts the options of a custom rendered select-multiple with entities in the labels', function () {
    $filter = new Filter(
        new ComponentAttributeBag(['data-field' => 'tags', 'input-type' => Filter::TYPE_SELECT_MULTIPLE]),
        customRendererCode: '<select multiple><option value="a">Alfa &amp; Ômega</option><option value="b" selected>Beta</option></select>',
    );

    expect($filter->getCustomRendererCodeWithXModel('inputFilters'))->toBe(
        '<select multiple name="inputFilters[tags][tags]" x-model="dtData()[\'inputFilters\'][\'tags\'][\'tags\']">'
        . '<option value="a">Alfa &amp; Ômega</option><option value="b" selected>Beta</option></select>'
    )
        ->and($filter->getSelectOptions())->toBe(['a' => 'Alfa & Ômega', 'b' => 'Beta']);
});

it('binds x-model to the hidden input of a masked input pair without an index suffix', function () {
    // A visible input masked on the client plus a hidden one holding the raw value: only the hidden
    // input is eligible for x-model, so it gets the bind with no suffix, while both get indexed names
    $result = renderedFilterCustomCode(
        ['data-field' => 'cpf', 'input-type' => Filter::TYPE_TEXT],
        '<div x-data="{ display: \'\' }"><input type="text" x-model="display" x-mask="999.999.999-99" placeholder="000.000.000-00"><input type="hidden"></div>',
    );

    expect($result)->toBe(
        '<div x-data="{ display: \'\' }">'
        . '<input type="text" x-model="display" x-mask="999.999.999-99" placeholder="000.000.000-00" name="inputFilters[cpf][cpf][0]" x-on:keydown.enter="applyFilters()">'
        . '<input type="hidden" name="inputFilters[cpf][cpf][1]" x-on:keydown.enter="applyFilters()" x-model="dtData()[\'inputFilters\'][\'cpf\'][\'cpf\']">'
        . '</div>'
    );
});

it('indexes name and x-model of every eligible input of a custom renderer', function () {
    $result = renderedFilterCustomCode(
        ['data-field' => 'created_at', 'input-type' => Filter::TYPE_DATE],
        '<div class="range"><input type="date" class="from"> até <input type="date" class="to"></div>',
    );

    expect($result)->toBe(
        '<div class="range">'
        . '<input type="date" class="from" name="inputFilters[created_at][created_at][0]" x-on:keydown.enter="applyFilters()" x-model="dtData()[\'inputFilters\'][\'created_at\'][\'created_at\'].0">'
        . ' até '
        . '<input type="date" class="to" name="inputFilters[created_at][created_at][1]" x-on:keydown.enter="applyFilters()" x-model="dtData()[\'inputFilters\'][\'created_at\'][\'created_at\'].1">'
        . '</div>'
    );
});

it('does not count an input that already has the attribute when indexing the others', function () {
    $result = renderedFilterCustomCode(
        ['data-field' => 'q', 'name' => 'q'],
        '<input type="text" name="custom"><input type="text"><select><option value="a">A</option></select>',
    );

    expect($result)->toBe(
        '<input type="text" name="custom" x-on:keydown.enter="applyFilters()" x-model="dtData()[\'inputFilters\'][\'q\'][\'q\'].0">'
        . '<input type="text" name="inputFilters[q][q][0]" x-on:keydown.enter="applyFilters()" x-model="dtData()[\'inputFilters\'][\'q\'][\'q\'].1">'
        . '<select name="inputFilters[q][q][1]" x-model="dtData()[\'inputFilters\'][\'q\'][\'q\'].2"><option value="a">A</option></select>'
    );
});

it('keeps the surrounding markup, text and utf-8 of a custom renderer', function () {
    $result = renderedFilterCustomCode(
        ['data-field' => 'descricao', 'label' => 'Descrição'],
        '<label for="d">Descrição &amp; ação</label>' . "\n"
        . '<input id="d" type="text" placeholder="Ex.: João" x-on:input="foo = $event.target.value" disabled>',
    );

    expect($result)->toBe(
        '<label for="d">Descrição &amp; ação</label>' . "\n"
        . '<input id="d" type="text" placeholder="Ex.: João" x-on:input="foo = $event.target.value" disabled name="inputFilters[descricao][descricao]" x-on:keydown.enter="applyFilters()" x-model="dtData()[\'inputFilters\'][\'descricao\'][\'descricao\']">'
    );
});

it('still inserts an attribute into an html string through insertAttributeValueIntoHTML', function () {
    $filter = new class(new ComponentAttributeBag(['data-field' => 'x']), '') extends Filter {
        public function insert(...$args): string
        {
            return $this->insertAttributeValueIntoHTML(...$args);
        }
    };

    $html = '<input><input x-model="a"><select></select>';

    expect($filter->insert($html, 'input,select', 'x-model', 'v', false, '.'))
        ->toBe('<input x-model="v.0"><input x-model="a"><select x-model="v.1"></select>')
        ->and($filter->insert($html, 'input,select', 'x-model', 'v', true, '[]'))
        ->toBe('<input x-model="v[0]"><input x-model="v[1]"><select x-model="v[2]"></select>')
        ->and($filter->insert('<input><input>', 'input', 'x-on:keydown.enter', 'go()', false))
        ->toBe('<input x-on:keydown.enter="go()"><input x-on:keydown.enter="go()">')
        ->and($filter->insert('<p>no inputs</p>', 'input', 'name', 'n', false, '[]'))
        ->toBe('<p>no inputs</p>')
        ->and(fn () => $filter->insert($html, 'input', 'name', 'n', false, '{}'))
        ->toThrow(\DomainException::class);
});

it('reports the missing select before a custom renderer is changed', function () {
    $filter = new Filter(
        new ComponentAttributeBag(['data-field' => 'status', 'input-type' => Filter::TYPE_SELECT, 'options' => ['1' => 'Yes']]),
        customRendererCode: '<input type="text" />',
    );

    expect(fn () => $filter->getCustomRendererCodeWithXModel('inputFilters'))
        ->toThrow(\LogicException::class, 'does not contain a <select> element');

    // Nothing was cached: the next call fails the same way
    expect(fn () => $filter->getCustomRendererCodeWithXModel('inputFilters'))
        ->toThrow(\LogicException::class, 'does not contain a <select> element');
});

it('reports a custom rendered select with no options and no options prop', function () {
    $filter = new Filter(
        new ComponentAttributeBag(['data-field' => 'status', 'input-type' => Filter::TYPE_SELECT]),
        customRendererCode: '<select><option value=""></option></select>',
    );

    expect(fn () => $filter->getCustomRendererCodeWithXModel('inputFilters'))
        ->toThrow(\LogicException::class, "no 'options' prop was provided");
});
