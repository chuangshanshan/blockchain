define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user_recharge/index' + location.search,
                    add_url: 'user_recharge/add',
                    edit_url: 'user_recharge/edit',
                    del_url: 'user_recharge/del',
                    multi_url: 'user_recharge/multi',
                    table: 'user_recharge',
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
                        {field: 'user.mobile', title: __('User.mobile')},
                        {field: 'hash_key', title: __('Hash_key')},
                        {field: 'from', title: __('转出钱包地址')},
                        {field: 'user.wallet', title: __('User.wallet')},
                        {field: 'amount', title: __('Amount')},
                        {field: 'is_merger', title: __('是否归并'),searchList: {"0":__('未归并'),"1":__('已归并')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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