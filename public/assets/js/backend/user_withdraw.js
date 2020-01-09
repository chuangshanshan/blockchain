define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user_withdraw/index' + location.search,
                    add_url: 'user_withdraw/add',
                    edit_url: 'user_withdraw/edit',
                    del_url: 'user_withdraw/del',
                    multi_url: 'user_withdraw/multi',
                    table: 'user_withdraw',
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
                        {field: 'user.mobile', title: __('User.mobile')},
                        {field: 'wallet_address', title: __('Wallet_address')},
                        {field: 'amount', title: __('Amount')},
                        {field: 'fee_rate', title: __('Fee_rate'), operate:'BETWEEN'},
                        {field: 'fee', title: __('Fee')},
                        {field: 'real_amount', title: __('Real_amount')},
                        {field: 'hash_key', title: __('Hash_key')},
                        {field: 'status', title: __('Status'),searchList: {"0":__('待审核'),"1":__('审核成功'),"2":__('处理中'),"9":__('提现失败'),"33":__('提现成功')}, formatter: Table.api.formatter.status},
                        {field: 'traded_time', title: __('Traded_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [{
                                name: 'detail',
                                text: __('detail'),
                                icon: 'fa fa-list',
                                classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                                url: 'user_withdraw/detail'
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