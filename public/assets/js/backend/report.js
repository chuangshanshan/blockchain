define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'report/index' + location.search,
                    add_url: 'report/add',
                    edit_url: 'report/edit',
                    del_url: 'report/del',
                    multi_url: 'report/multi',
                    table: 'report',
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
                        {field: 'id', title: __('Id')},
                        {field: 'user.id', title: __('举报人ID')},
                        {field: 'user.nickname', title: __('举报人昵称')},
                        {field: 'user.mobile', title: __('举报人手机')},
                        {field: 'association_name', title: __('Association_name')},
                        {field: 'association_admin', title: __('Association_admin')},
                        {field: 'association_aide', title: __('Association_aide')},
                        {field: 'accused_user', title: __('Accused_user')},
                        // {field: 'screenshot_image', title: __('Screenshot_image'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'screenshot_image', title: __('Screenshot_image'), events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [{
                                name: 'detail',
                                text: __('Detail'),
                                icon: 'fa fa-list',
                                classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                                url: 'report/detail'
                            }],
                            formatter: Table.api.formatter.operate}
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
        detail: function () {
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