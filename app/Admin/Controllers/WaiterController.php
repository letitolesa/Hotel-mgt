<?php

namespace App\Admin\Controllers;

use App\Models\Waiter;
use App\Models\Employee;
use App\Models\Section;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class WaiterController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Waiter Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Waiter());

        $grid->column('id', 'ID')->sortable();
        $grid->column('code', 'Waiter Code')->sortable()->badge('primary');
        $grid->column('employee.employee_number', 'Employee #')->sortable();
        $grid->column('employee.full_name', 'Waiter Name')->display(function () {
            return $this->employee->first_name . ' ' . $this->employee->last_name;
        });
        $grid->column('section.name', 'Assigned Section')->sortable();
        $grid->column('assigned_at', 'Assigned Date')->display(function ($date) {
            return $date ? $date->format('Y-m-d') : '-';
        })->sortable();
        $grid->column('is_active', 'Status')->display(function ($status) {
            return $status ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>';
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('code', 'Waiter Code');
            $filter->equal('section_id', 'Section')->select(Section::active()->pluck('name', 'id'));
            $filter->equal('is_active', 'Status')->select([
                '' => 'All',
                1 => 'Active',
                0 => 'Inactive'
            ]);
            $filter->between('assigned_at', 'Assigned Date')->date();
        });

        // Quick search
        $grid->quickSearch('code');

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
        $show = new Show(Waiter::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('code', 'Waiter Code');
        
        // Employee details
        $show->field('employee.employee_number', 'Employee Number');
        $show->field('employee.first_name', 'First Name');
        $show->field('employee.last_name', 'Last Name');
        $show->field('employee.email', 'Email');
        $show->field('employee.phone', 'Phone');
        
        $show->field('section.name', 'Assigned Section');
        $show->field('assigned_at', 'Assigned At');
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
        $form = new Form(new Waiter());

        // Waiter Code
        $form->text('code', 'Waiter Code')
            ->required()
            ->rules('required|string|min:2|max:10|unique:waiters,code,{{id}}|regex:/^[A-Z0-9]+$/')
            ->help('Unique waiter code (2-10 characters, uppercase letters, numbers only)')
            ->placeholder('e.g., W001, WAITER01');

        // Employee Selection
        $form->select('employee_id', 'Employee')
            ->options(function () {
                // Get employees who are not already assigned as waiters
                $assignedIds = Waiter::pluck('employee_id')->toArray();
                return Employee::whereNotIn('id', $assignedIds)
                    ->orWhere('id', request()->route('id'))
                    ->get()
                    ->mapWithKeys(function ($employee) {
                        return [$employee->id => $employee->employee_number . ' - ' . $employee->first_name . ' ' . $employee->last_name];
                    });
            })
            ->required()
            ->rules('required|integer|exists:employees,id|unique:waiters,employee_id,{{id}}')
            ->help('Select the employee to assign as waiter');

        // Section Assignment
        $form->select('section_id', 'Assigned Section')
            ->options(Section::active()->pluck('name', 'id'))
            ->required()
            ->rules('required|integer|exists:sections,id')
            ->help('Select the section this waiter will be assigned to');

        // Assigned At
        $form->datetime('assigned_at', 'Assigned Date')
            ->rules('required|date')
            ->default(now())
            ->help('Date and time when waiter was assigned');

        // Active Status
        $form->switch('is_active', 'Active')
            ->default(1)
            ->help('Enable or disable this waiter assignment');

        // Display employee info helper
        $form->html(function () {
            return '<div class="alert alert-info">
                <i class="icon-info-circle"></i> 
                <strong>Note:</strong> Only employees not already assigned as waiters are shown in the dropdown.
            </div>';
        }, 'Employee Selection Info');

        // Display timestamps in edit mode
        if ($form->isEditing()) {
            $form->divider();
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        }

        return $form;
    }
}