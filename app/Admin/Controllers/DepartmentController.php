<?php

namespace App\Admin\Controllers;

use App\Models\Department;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class DepartmentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Department Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Department());

        $grid->column('id', 'ID')->sortable();
        $grid->column('code', 'Department Code')->sortable();
        $grid->column('name', 'Department Name')->sortable();
        $grid->column('description', 'Description')->display(function ($desc) {
            return $desc ? substr($desc, 0, 50) . (strlen($desc) > 50 ? '...' : '') : '-';
        });
        $grid->column('is_active', 'Status')->display(function ($status) {
            return $status ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>';
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('code', 'Code');
            $filter->like('name', 'Name');
            $filter->equal('is_active', 'Status')->select([
                '' => 'All',
                1 => 'Active',
                0 => 'Inactive'
            ]);
        });

        // Quick search
        $grid->quickSearch('code', 'name');

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Department::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('code', 'Department Code');
        $show->field('name', 'Department Name');
        $show->field('description', 'Description');
        $show->field('is_active', 'Status')->using([1 => 'Active', 0 => 'Inactive']);
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Department());

        $form->text('code', 'Department Code')
            ->required()
            ->rules([
                'required',
                'string',
                'min:2',
                'max:20',
                'unique:departments,code,{{id}}',
                'regex:/^[A-Z0-9_]+$/'
            ])
            ->help('Unique code (2-20 characters, uppercase letters, numbers, underscore only)')
            ->placeholder('e.g., HR, IT, FIN');

        $form->text('name', 'Department Name')
            ->required()
            ->rules([
                'required',
                'string',
                'min:3',
                'max:100',
                'unique:departments,name,{{id}}'
            ])
            ->help('Full department name (3-100 characters)')
            ->placeholder('e.g., Human Resources, Information Technology');

        $form->textarea('description', 'Description')
            ->rules([
                'nullable',
                'string',
                'max:500'
            ])
            ->rows(3)
            ->help('Brief description (max 500 characters)')
            ->placeholder('Enter department description...');

        $form->switch('is_active', 'Active')
            ->default(1)
            ->help('Enable or disable this department');

        // Display timestamps in edit form
        if ($form->isEditing()) {
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        }

        // Form validation messages
        $form->saving(function (Form $form) {
            // Additional validation if needed
            $code = $form->code;
            $name = $form->name;
            
            if (strlen($code) < 2) {
                throw new \Exception('Department code must be at least 2 characters.');
            }
            
            if (strlen($name) < 3) {
                throw new \Exception('Department name must be at least 3 characters.');
            }
        });

        return $form;
    }
}