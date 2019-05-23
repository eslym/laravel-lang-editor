<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Translations Editor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css" integrity="sha256-9mbkOfVho3ZPXfM7W8sV2SndrGDuh7wuyLjtsWeTI1Q=" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/dataTables.semanticui.min.css" integrity="sha384-NWDSc00/CBkibLhoKVtYHuQj8VuJNbeHZDTpWhMKBFDPTLSgT2l3HSJjItrJl+B9" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha384-vk5WoKIaW/vJyUAd9n/wmopsmNhiy+L2Z+SBxGYnUkunIxVxAv/UtMOhba/xskxh" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js" integrity="sha384-rgWRqC0OFPisxlUvl332tiM/qmaNxnlY46eksSZD84t+s2vZlqGeHrncwIRX7CGp" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.10.19/js/dataTables.semanticui.min.js" integrity="sha384-5IYbSnFd6TeNKhOf8CO6LuJpN4IuBiaYwOsPv7CQsbF8sctyVeh7GU3OlfvFBW6n" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js" integrity="sha256-t8GepnyPmw9t+foMh3mKNvcorqNHamSKtKRxxpUEgFI=" crossorigin="anonymous"></script>
    <script>
        @php($columns = array_map(function($val){ return ['data'=>$val];}, $languages))
        @php(array_unshift($columns, ['data'=>null], ['data'=> "key"]))
        @php($range = range(2, count($columns) - 1))
        let columns = @json($columns);
        let csrf_token = @json(csrf_token());
        $.fn.dataTable.ext.errMode = 'none';
        $(document).ready(()=>{
            window.langTable = $('table#langs')
                .on('click', 'td:nth-child(2)', function(){
                    $(this).siblings('td:first-child')
                        .find('.checkbox')
                        .checkbox('toggle');
                })
                .on('dblclick', 'textarea', function(){
                    let i = $(this);
                    let m = $('#editModal')
                    m.find('.header').text(i.data('key'));
                    m.find('label').text(i.data('lang'));
                    m.find('textarea').val(i.val())
                        .data('key', i.data('key'))
                        .data('lang', i.data('lang'));
                    m.modal('show');
                })
                .dataTable({
                    ajax: @json(route('lang-editor::trans')),
                    columns: columns,
                    columnDefs: [
                        {
                            render: (data, type, record, cell) => {
                                let div = $('<div>');
                                div.html('<div class="ui fitted checkbox"><input type="checkbox"><label></label></div>');
                                div.find('input')
                                    .attr('id', 'cb_'+record.key)
                                    .attr('data-key', record.key)
                                    .attr('data-row', cell.row)
                                    .siblings('label')
                                    .attr('for', 'cb_'+record.key);
                                return div.html();
                            },
                            orderable: false,
                            className: 'collapsing',
                            targets: 0
                        },
                        {
                            render: (data, type, record, cell)=>{
                                let div = $('<div>');
                                data = div.html(data).html();
                                div.html('<textarea class="cell" rows="0" onchange="update(this)" placeholder="Not Translated"></textarea>');
                                div.find('textarea')
                                    .text(data)
                                    .attr('data-key', record.key)
                                    .attr('data-lang', columns[cell.col].data)
                                    .attr('data-row', cell.row)
                                    .attr('data-col', cell.col);
                                return div.html();
                            },
                            targets: @json($range)
                        },
                        {
                            className: 'two wide',
                            targets: 1
                        }
                    ],
                    order: [[ 1, 'asc' ]]
                }).api();
            langTable.columns().every(function () {
                let col = this;
                $('input', this.footer()).on('keyup change', function () {
                    if (col.search() !== this.value) {
                        col.search(this.value)
                            .draw();
                    }
                });
            });
            showCol(document.getElementById('colSelect'));
            $('#insertModal')
                .modal({
                    approve: '.approve'
                })
                .modal('attach events', '#btnInsert');
        });
        function update(element){
            let el = $(element);
            langTable.cell(el.attr('data-row'), el.attr('data-col')).data(el.val());
            let data = {
                _token: csrf_token,
                key: el.data('key'),
                lang: el.data('lang'),
                value: el.val(),
            };
            $.post(@json(route('lang-editor::update')), data);
        }
        function showCol(cols){
            let show = $(cols).val();
            for (i = 2; i < columns.length; i++){
                langTable.column(i).visible(show.indexOf(i.toString()) !== -1);
            }
        }
        function deleteRecord(){
            let keys = $('input[type=checkbox]:checked').map(function(){
                console.log(this);
                return $(this).data('key');
            }).toArray();
            if(keys.length > 0){
                let data = {
                    _token: csrf_token,
                    keys: keys
                };
                $.post(@json(route('lang-editor::delete')), data);
                $('input[type=checkbox]:checked').each(function(){
                    langTable.row($(this).closest('tr')).remove();
                });
                langTable.draw();
            }
        }
        function textChange(el){
            let sel = 'textarea[data-key="'+$(el).data('key')+'"][data-lang="'+$(el).data('lang')+'"]';
            update($(sel).val($(el).val()));
        }
    </script>
    <style>
        body{
            padding: 20px;
        }
        textarea{
            overflow: auto;
            white-space: pre;
        }
        textarea.cell{
            resize: none;
            line-height: 1.25em;
            height: 1.25em;
            width: 100%;
            border: none;
            background: none;
            overflow: hidden;
        }
    </style>
</head>
<body>
<div class="ui basic segment">
    <label for="colSelect">Show Languages: </label>
    <select id="colSelect" class="ui search dropdown" multiple onchange="showCol(this)">
        @foreach($languages as $key => $lang)
            <option value="{{$key + 2}}" selected>{{$lang}}</option>
        @endforeach
    </select>
    <script>
        $('#colSelect').dropdown();
    </script>
</div>
<div class="ui segment">
    <button  class="ui icon green button" id="btnInsert">
        <i class="plus icon"></i>
        Insert
    </button>
    <button class="ui icon blue button" onclick="$('.checkbox').checkbox('toggle');">
        <i class="check square outline icon"></i>
        Toggle Selections
    </button>
    <button class="ui icon purple button" onclick="$('.checkbox').checkbox('uncheck');">
        <i class="square outline icon"></i>
        Clear Selections
    </button>
    <button class="ui icon red button" onclick="deleteRecord()">
        <i class="trash icon"></i>
        Delete
    </button>
</div>
<table class="ui striped compact small selectable definition fixed celled table" id="langs" style="width: 100%">
    <thead>
    <tr>
        <th style="width: 20px;"></th>
        <th class="center aligned">Key</th>
        @foreach($languages as $lang)
            <th class="center aligned">{{$lang}}</th>
        @endforeach
    </tr>
    </thead>
    <tfoot>
    <tr>
        <th></th>
        <th><div class="ui fluid input"><input placeholder="Key"/></div></th>
        @foreach($languages as $lang)
            <th><div class="ui fluid input"><input placeholder="{{$lang}}"/></div></th>
        @endforeach
    </tr>
    </tfoot>
</table>
<div class="ui mini modal" id="insertModal">
    <div class="header">Insert New Translation</div>
    <div class="content">
        <div class="ui form">
            <div class="ui left labeled fluid input">
                <label class="ui label" for="insert-key">
                    Key
                </label>
                <input id="insert-key" type="text" placeholder="package::group.key" >
            </div>
        </div>
    </div>
    <div class="actions">
        <button class="ui green approve button" onclick="langTable.row.add({key:$('#insert-key').val()}).draw()">Insert</button>
    </div>
</div>
<div class="ui small modal" id="editModal">
    <div class="header"></div>
    <div class="content">
        <div class="ui form">
            <div class="fluid field">
                <label for="text-trans">
                </label>
                <textarea id="text-trans" placeholder="Not Translated" onchange="textChange(this)"></textarea>
            </div>
        </div>
    </div>
    <div class="actions">
        <button class="ui red approve button">Close</button>
    </div>
</div>
</body>
</html>