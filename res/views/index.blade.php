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
        @php(array_unshift($columns, ['data'=> "key"]))
        @php($range = range(1, count($columns) - 1))
        let columns = @json($columns);
        $.fn.dataTable.ext.errMode = 'none';
        $(document).ready(()=>{
            window.langTable = $('#langs').dataTable({
                ajax: @json(route('lang-editor::trans')),
                columns: columns,
                columnDefs: [
                    {
                        render: (data, type, record, cell)=>{
                            let div = $('<div>');
                            data = div.html(data).html();
                            div.html('<div class="ui fluid small input"><input onchange="update(this);" placeholder="Not Translated"></div>');
                            div.find('input')
                                .attr('value', data)
                                .attr('data-key', record.key)
                                .attr('data-lang', columns[cell.col].data)
                                .attr('data-row', cell.row)
                                .attr('data-col', cell.col);
                            return div.html();
                        },
                        targets: @json($range)
                    }
                ]
            }).api();
            showCol(document.getElementById('colSelect'));
        });
        function update(element){
            let el = $(element);
            langTable.cell(el.attr('data-row'), el.attr('data-col')).data(el.val());
            let data = {
                key: el.attr('data-key'),
                lang: el.attr('data-lang'),
                value: el.val(),
            };
            $.post(@json(route('lang-editor::update')), data);
        }
        function showCol(cols){
            let show = $(cols).val();
            for (i = 1; i < columns.length; i++){
                langTable.column(i).visible(show.indexOf(i.toString()) !== -1);
            }
        }
    </script>
    <style>
        body{
            padding: 20px;
        }
    </style>
</head>
<body>
<div class="ui basic segment">
    <label for="colSelect">Show Languages: </label>
    <select id="colSelect" class="ui search dropdown" multiple onchange="showCol(this)">
        @foreach($languages as $key => $lang)
            <option value="{{$key + 1}}" selected>{{$lang}}</option>
        @endforeach
    </select>
    <script>
        $('#colSelect').dropdown();
    </script>
</div>
<table class="ui striped compact small selectable fixed table" id="langs" style="width: 100%">
    <thead>
    <tr>
        <th class="two wide center aligned">Key</th>
        @foreach($languages as $lang)
            <th class="center aligned">{{$lang}}</th>
        @endforeach
    </tr>
    </thead>
    <tfoot>
    <tr>
        <th class="two wide center aligned">Key</th>
        @foreach($languages as $lang)
            <th class="center aligned">{{$lang}}</th>
        @endforeach
    </tr>
    </tfoot>
</table>
<div class="ui popup">
    <div class="content">
        <div class="ui form">
            <div class="ui icon input">
                <textarea></textarea>
                <i class="inverted circular save link icon"></i>
            </div>
        </div>
    </div>
</div>
</body>
</html>