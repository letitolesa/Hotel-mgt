<?php

namespace App\Admin\Controllers;

use App\Models\Position;
use App\Models\Department;
use App\Models\User;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class PositionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Position Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Position());

        $grid->column('id', 'ID')->sortable();
        $grid->column('code', 'Position Code')->sortable();
        $grid->column('title', 'Position Title')->sortable();
        $grid->column('department.name', 'Department')->sortable();
        $grid->column('base_salary', 'Base Salary')->display(function ($salary) {
            return $salary ? 'ETB ' . number_format($salary, 2) : '-';
        });
        $grid->column('requires_shift', 'Shift Required')->display(function ($shift) {
            return $shift ? '<span class="label label-warning">Yes</span>' : '<span class="label label-default">No</span>';
        });
        $grid->column('is_active', 'Status')->display(function ($status) {
            return $status ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>';
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('code', 'Code');
            $filter->like('title', 'Title');
            $filter->equal('department_id', 'Department')->select(Department::active()->pluck('name', 'id'));
            $filter->equal('requires_shift', 'Shift Required')->select([
                '' => 'All',
                1 => 'Yes',
                0 => 'No'
            ]);
            $filter->equal('is_active', 'Status')->select([
                '' => 'All',
                1 => 'Active',
                0 => 'Inactive'
            ]);
        });

        // Quick search
        $grid->quickSearch('code', 'title');

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
        $show = new Show(Position::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('code', 'Position Code');
        $show->field('title', 'Position Title');
        $show->field('department.name', 'Department');
        $show->field('base_salary', 'Base Salary')->as(function ($salary) {
            return $salary ? 'ETB ' . number_format($salary, 2) : 'Not Set';
        });
        $show->field('requires_shift', 'Shift Required')->using([1 => 'Yes', 0 => 'No']);
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
        $form = new Form(new Position());

        // Department selection
        $form->select('department_id', 'Department')
            ->options(Department::active()->pluck('name', 'id'))
            ->required()
            ->rules('required|integer|exists:departments,id')
            ->help('Select the department this position belongs to');

        // Position code
        $form->text('code', 'Position Code')
            ->required()
            ->rules('required|string|min:2|max:20|unique:positions,code,{{id}}|regex:/^[A-Z0-9_]+$/')
            ->help('Unique code (2-20 characters, uppercase letters, numbers, underscore only)')
            ->placeholder('e.g., HR_MGR, DEV_SR');

        // Position title
        $form->text('title', 'Position Title')
            ->required()
            ->rules('required|string|min:3|max:100')
            ->help('Full position title (3-100 characters)')
            ->placeholder('e.g., Senior Developer, HR Manager');

        // Base salary with ETB currency
        $form->currency('base_salary', 'Base Salary')
            ->symbol('ETB')
            ->rules('nullable|numeric|min:0|max:99999999.99')
            ->help('Base salary for this position (optional)')
            ->placeholder('0.00');

        // Shift requirement
        $form->switch('requires_shift', 'Requires Shift Work')
            ->default(0)
            ->help('Enable if this position requires shift work');

        // Status
        $form->switch('is_active', 'Active')
            ->default(1)
            ->help('Enable or disable this position');

        // Set audit fields - Check if user exists
        $form->saving(function (Form $form) {
            $userId = auth()->user()->id;
            
            // Check if user exists in users table
            if (!User::find($userId)) {
                // If user doesn't exist, set to null
                if ($form->isCreating()) {
                    $form->created_by = null;
                    $form->updated_by = null;
                }
                if ($form->isEditing()) {
                    $form->updated_by = null;
                }
            } else {
                if ($form->isCreating()) {
                    $form->created_by = $userId;
                    $form->updated_by = $userId;
                }
                if ($form->isEditing()) {
                    $form->updated_by = $userId;
                }
            }
        });

        return $form;
    }
}