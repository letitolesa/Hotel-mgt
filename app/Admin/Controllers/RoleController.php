<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends AdminController
{
    protected $title = 'Roles';

    protected function grid()
    {
        $grid = new Grid(new Role());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('Name'))->sortable();
        $grid->column('guard_name', __('Guard Name'));
        $grid->column('permissions', __('Permissions'))->display(function ($permissions) {
            return collect($permissions)->pluck('name')->implode(', ');
        });


        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Role::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Name'));
        $show->field('guard_name', __('Guard Name'));
        $show->field('permissions', __('Permissions'))->as(function ($permissions) {
            return $permissions->pluck('name')->implode(', ');
        });
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new Role());

        $form->text('name', __('Name'))->rules('required');
        $form->text('guard_name', __('Guard Name'))->default('web');
        
        $form->multipleSelect('permissions', __('Permissions'))
            ->options(Permission::all()->pluck('name', 'id'))
            ->rules('required');

        return $form;
    }
}