<!-- Backpack Table Field Type -->

<?php
    $max = isset($field['max']) && (int) $field['max'] > 0 ? $field['max'] : -1;
    $min = isset($field['min']) && (int) $field['min'] > 0 ? $field['min'] : -1;
    $item_name = strtolower(isset($field['entity_singular']) && !empty($field['entity_singular']) ? $field['entity_singular'] : $field['label']);

    $items = old(square_brackets_to_dots($field['name'])) ?? $field['value'] ?? $field['default'] ?? '';

    // make sure not matter the attribute casting
    // the $items variable contains a properly defined JSON string
    if (is_array($items)) {
        if (count($items)) {
            $items = json_encode($items);
        } else {
            $items = '[]';
        }
    } elseif (is_string($items) && !is_array(json_decode($items))) {
        $items = '[]';
    }

    echo '<pre>'; var_dump($items); echo '</pre>';
?>
<div data-field-type="table" data-field-name="{{ $field['name'] }}" @include('crud::inc.field_wrapper_attributes') >

    <label>{!! $field['label'] !!}</label>
    @include('crud::inc.field_translatable_icon')

    <input class="array-json" type="hidden" name="{{ $field['name'] }}">

    <div class="array-container form-group">

        <table class="table table-sm table-striped m-b-0" data-items="{{ $items }}" data-max="{{$max}}" data-min="{{$min}}" data-maxErrorTitle="{{trans('backpack::crud.table_cant_add', ['entity' => $item_name])}}" data-maxErrorMessage="{{trans('backpack::crud.table_max_reached', ['max' => $max])}}">

            <thead>
                <tr>
                    @foreach( $field['columns'] as $column )
                    <th style="font-weight: 600!important;">
                        {{ $column }}
                    </th>
                    @endforeach
                    <th class="text-center"> {{-- <i class="fa fa-sort"></i> --}} </th>
                    <th class="text-center"> {{-- <i class="fa fa-trash"></i> --}} </th>
                </tr>
            </thead>

            <tbody class="table-striped items sortableOptions">

                <tr class="array-row clonable" style="display: none;">
                    @foreach( $field['columns'] as $column => $label)
                    <td>
                        <input class="form-control form-control-sm" type="text" name="item.{{ $column }}">
                    </td>
                    @endforeach
                    <td>
                        <span class="btn btn-sm btn-light sort-handle pull-right"><span class="sr-only">sort item</span><i class="fa fa-sort" role="presentation" aria-hidden="true"></i></span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-light removeItem" type="button"><span class="sr-only">delete item</span><i class="fa fa-trash" role="presentation" aria-hidden="true"></i></button>
                    </td>
                </tr>

            </tbody>

        </table>

        <div class="array-controls btn-group m-t-10">
            <button class="btn btn-sm btn-light" type="button" data-button-type="addItem"><i class="fa fa-plus"></i> {{trans('backpack::crud.add')}} {{ $item_name }}</button>
        </div>

    </div>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
</div>

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@if ($crud->checkIfFieldIsFirstOfItsType($field))

    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
        {{-- YOUR JS HERE --}}
        <script type="text/javascript" src="{{ asset('packages/jquery-ui-dist/jquery-ui.min.js') }}"></script>

        <script>
            jQuery(document).ready(function($) {
                $('[data-field-type=table]').each(function() {
                    var $tableWrapper = $(this);

                    var $max = $tableWrapper.find('table').attr('data-max');
                    var $min = $tableWrapper.find('table').attr('data-min');

                    var $maxErrorTitle = $tableWrapper.find('table').attr('data-maxErrorTitle');
                    var $maxErrorMessage = $tableWrapper.find('table').attr('data-maxErrorMessage');

                    var $tbody = $(this).find('tbody');
                    var $items = $.parseJSON($tableWrapper.find('table').attr('data-items'));

                    // populate the table field with the initial data in the JSON
                    if($items != '[]') {

                        $.each($items, function() {
                            addItem();

                            $.each(this, function(k , v) {
                                console.log("writing "+v+" to "+k);
                                $tableWrapper.find('tbody tr:last').find('input[name="item.' + k + '"]').val(v);

                                console.log('updateTableFieldJson because there ARE rows');
                                updateTableFieldJson();
                            });

                        });
                        
                    }

                    $(this).find('.sortableOptions').sortable({
                        handle: '.sort-handle',
                        axis: 'y',
                        helper: function(e, ui) {
                            ui.children().each(function() {
                                $(this).width($(this).width());
                            });
                            return ui;
                        },
                        update: function( event, ui ) {                        

                            console.log('updateTableFieldJson because sortable');
                            updateTableFieldJson();
                        }
                    });

                    $tableWrapper.find('[data-button-type=addItem]').click(function() {
                        if($max > -1) {
                            var totalRows = $tableWrapper.find('tbody tr:visible').length;

                            if(totalRows < $max) {
                                addItem();
                            } else {
                                new Noty({
                                  type: "warning",
                                  text: "<strong>"+$maxErrorTitle+"</strong><br>"+$maxErrorMessage
                                }).show();
                            }
                        } else {
                            addItem();
                        }
                    });

                    function addItem() {
                        $tableWrapper.find('tbody').append($tableWrapper.find('tbody .clonable').clone().show().removeClass('clonable'));

                        console.log('updateTableFieldJson because add item');
                        updateTableFieldJson();
                    }

                    $(this).on('click', '.remove-item', function() {
                        $(this).closest('tr').remove();

                        console.log('updateTableFieldJson because remove item');
                        updateTableFieldJson();

                        return false;
                    });

                    $(this).on('change', $tbody, function() {
                        console.log('updateTableFieldJson because on change');
                        updateTableFieldJson();
                    });


                    // add minimum rows if needed
                    var itemCount = $(this).find('tbody tr').not('.clonable').length;
                    if($min > 0 && itemCount < $min) {
                        $rowsToAdd = Number($min) - Number(itemCount);

                        for(var i = 0; i < $rowsToAdd; i++){
                            addItem();
                        }
                    }

                    function updateTableFieldJson() {
                        var $rows = $tableWrapper.find('tbody tr:visible');
                        var $fieldName = $tableWrapper.attr('data-field-name');
                        var $hiddenField = $($tableWrapper).find('input[name='+$fieldName+']');

                        console.log("Counting "+$rows.length+" rows.");

                        var json = '[';
                        var otArr = [];
                        var tbl2 = $rows.each(function(i) {
                            x = $(this).children().closest('td').find('input');
                            var itArr = [];
                            x.each(function() {
                                if(this.value.length > 0) {
                                    itArr.push('"' + this.name.replace('item.','') + '":"' + this.value + '"');
                                }
                            });
                            otArr.push('{' + itArr.join(',') + '}');
                        })
                        json += otArr.join(",") + ']';

                        var totalRows = $rows.length;

                        $hiddenField.val( totalRows ? json : null );

                        console.log('HIDDEN INPUT value was set to: '+(totalRows ? json : null));
                    }

                    // on page load, make sure the input has the old values
                    updateTableFieldJson();
                });
            });
        </script>
    @endpush
@endif
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
