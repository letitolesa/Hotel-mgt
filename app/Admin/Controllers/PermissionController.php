<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;
use Spatie\Permission\Models\Permission;

class PermissionController extends AdminController
{
    protected $title = 'Permissions';

    protected function grid()
    {
        $grid = new Grid(new Permission());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('Name'))->sortable();
        $grid->column('guard_name', __('Guard Name'));
  

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Permission::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Name'));
        $show->field('guard_name', __('Guard Name'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new Permission());

        $form->text('name', __('Name'))->rules('required');
        $form->text('guard_name', __('Guard Name'))->default('web');

        return $form;
    }
}