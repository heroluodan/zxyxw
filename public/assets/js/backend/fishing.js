define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fishing/index',
                    add_url: 'fishing/add',
                    edit_url: 'fishing/edit',
                    del_url: 'fishing/del',
                    multi_url: 'fishing/multi',
                    table: 'fishing',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'uid', title: __('Uid')},
                        {field: 'num', title: __('Num')},
                        {field: 'use_time', title: __('Use_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'use_date', title: __('Use_date'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'get_time', title: __('Get_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'get_date', title: __('Get_date'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'is_get', title: __('Is_get'), visible:false, searchList: {"4":__('Is_get 4')}},
                        {field: 'is_get_text', title: __('Is_get'), operate:false},
                        {field: 'is_pull', title: __('Is_pull'), visible:false, searchList: {"4":__('Is_pull 4')}},
                        {field: 'is_pull_text', title: __('Is_pull'), operate:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});