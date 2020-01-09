define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'questions/index' + location.search,
                    add_url: 'questions/add',
                    edit_url: 'questions/edit',
                    del_url: 'questions/del',
                    multi_url: 'questions/multi',
                    table: 'questions',
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
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'question', title: __('Question')},
                        {field: 'optionA', title: __('Optiona'), operate: false},
                        {field: 'optionB', title: __('Optionb'), operate: false},
                        {field: 'optionC', title: __('Optionc'), operate: false},
                        {field: 'optionD', title: __('Optiond'), operate: false},
                        {field: 'answer', title: __('Answer'), searchList: {"1":__('Answer 1'),"2":__('Answer 2'),"3":__('Answer 3'),"4":__('Answer 4')}, formatter: Table.api.formatter.normal, operate: false},
                        {field: 'score', title: __('Score')},
                        {field: 'complexity', title: __('Complexity'), searchList: {"1":__('Complexity 1'),"2":__('Complexity 2'),"3":__('Complexity 3')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.selectShow();
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.selectShow();
            Controller.api.bindevent();
            $('body').ready(function () {
                setTimeout(function () {
                    $('#c-type').change();
                },200);
            })
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
        selectShow:function () {
            $('#c-type').change(function () {
                if($(this).val() == 1){
                    $('#c-optionC').parent().parent().show();
                    $('#c-optionD').parent().parent().show();
                    for (var i in $('#c-answer').parent().find('.dropdown-menu.open .dropdown-menu.inner li')){
                        if(i == 2 || i == 3){
                            $($('#c-answer').parent().find('.dropdown-menu.open .dropdown-menu.inner li')[i]).show()
                        }
                    }
                }else{
                    $('#c-optionC').parent().parent().hide();
                    $('#c-optionD').parent().parent().hide();
                    for (var i in $('#c-answer').parent().find('.dropdown-menu.open .dropdown-menu.inner li')){
                        if(i == 2 || i == 3){
                            $($('#c-answer').parent().find('.dropdown-menu.open .dropdown-menu.inner li')[i]).hide()
                        }
                    }
                }
            })
        }
    };
    return Controller;
});