define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user_red_packet/index' + location.search,
                    add_url: 'user_red_packet/add',
                    edit_url: 'user_red_packet/edit',
                    del_url: 'user_red_packet/del',
                    multi_url: 'user_red_packet/multi',
                    table: 'user_red_packet',
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
                        {field: 'user.mobile', title: __('用户手机')},
                        {field: 'association_name', title: __('Association_name')},
                        {field: 'amount_text', title: __('Amount')},
                        {field: 'screenshot_image', title: __('Screenshot_image'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'is_shared', title: __('Is_shared'), searchList: {"0":__('Is_shared 0'),"1":__('Is_shared 1')}, formatter: Table.api.formatter.normal},
                        {field: 'audit_status', title: __('审核状态'), searchList: {"0":__('待审核'),"1":__('通过'),"2":__('拒绝')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [{
                                name: 'detail',
                                text: __('detail'),
                                icon: 'fa fa-list',
                                classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                                url: 'user_red_packet/detail'
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