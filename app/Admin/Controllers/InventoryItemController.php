<?php

namespace App\Admin\Controllers;

use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class InventoryItemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Inventory Item Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new InventoryItem());

        $grid->column('id', 'ID')->sortable();
        $grid->column('code', 'Item Code')->sortable()->badge('primary');
        $grid->column('name', 'Item Name')->sortable();
        $grid->column('category.name', 'Category')->sortable();
        $grid->column('department.name', 'Department')->sortable();
        $grid->column('quantity', 'Quantity')->sortable()->display(function ($qty) {
            return number_format($qty, 2);
        });
        $grid->column('unit_of_measure', 'UOM')->sortable()->badge('info');
        $grid->column('unit_cost', 'Unit Cost')->display(function ($cost) {
            return $cost ? 'ETB ' . number_format($cost, 2) : '-';
        });
        $grid->column('total_value', 'Total Value')->display(function ($value) {
            return $value ? 'ETB ' . number_format($value, 2) : '-';
        });
        $grid->column('status', 'Status')->display(function ($status) {
            $colors = [
                'in_stock' => 'success',
                'low_stock' => 'warning',
                'out_of_stock' => 'danger',
                'discontinued' => 'default'
            ];
            $labels = [
                'in_stock' => 'In Stock',
                'low_stock' => 'Low Stock',
                'out_of_stock' => 'Out of Stock',
                'discontinued' => 'Discontinued'
            ];
            $color = $colors[$status] ?? 'default';
            $label = $labels[$status] ?? $status;
            return "<span class='label label-{$color}'>" . strtoupper(str_replace('_', ' ', $label)) . "</span>";
        });
        $grid->column('is_active', 'Active')->display(function ($active) {
            return $active ? '<span class="label label-success">Yes</span>' : '<span class="label label-danger">No</span>';
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('code', 'Code');
            $filter->like('name', 'Name');
            $filter->equal('category_id', 'Category')->select(Category::active()->pluck('name', 'id'));
            $filter->equal('department_id', 'Department')->select(Department::active()->pluck('name', 'id'));
            $filter->equal('status', 'Status')->select([
                '' => 'All',
                'in_stock' => 'In Stock',
                'low_stock' => 'Low Stock',
                'out_of_stock' => 'Out of Stock',
                'discontinued' => 'Discontinued'
            ]);
            $filter->equal('is_active', 'Active')->select([
                '' => 'All',
                1 => 'Yes',
                0 => 'No'
            ]);
            $filter->between('quantity', 'Quantity Range');
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
        $show = new Show(InventoryItem::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('code', 'Item Code');
        $show->field('name', 'Item Name');
        $show->field('description', 'Description');
        $show->field('category.name', 'Category');
        $show->field('department.name', 'Department');
        $show->field('unit_of_measure', 'Unit of Measure');
        $show->field('quantity', 'Current Quantity')->as(function ($qty) {
            return number_format($qty, 2);
        });
        $show->field('minimum_quantity', 'Minimum Quantity')->as(function ($qty) {
            return $qty ? number_format($qty, 2) : 'Not Set';
        });
        $show->field('maximum_quantity', 'Maximum Quantity')->as(function ($qty) {
            return $qty ? number_format($qty, 2) : 'Not Set';
        });
        $show->field('reorder_point', 'Reorder Point')->as(function ($qty) {
            return $qty ? number_format($qty, 2) : 'Not Set';
        });
        $show->field('unit_cost', 'Unit Cost')->as(function ($cost) {
            return $cost ? 'ETB ' . number_format($cost, 2) : 'Not Set';
        });
        $show->field('total_value', 'Total Value')->as(function ($value) {
            return $value ? 'ETB ' . number_format($value, 2) : 'Not Set';
        });
        $show->field('status', 'Status')->using([
            'in_stock' => 'In Stock',
            'low_stock' => 'Low Stock',
            'out_of_stock' => 'Out of Stock',
            'discontinued' => 'Discontinued'
        ]);
        $show->field('notes', 'Notes');
        $show->field('is_active', 'Active')->using([1 => 'Yes', 0 => 'No']);
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
        $form = new Form(new InventoryItem());

        $form->tab('Basic Information', function ($form) {
            // Item code
            $form->text('code', 'Item Code')
                ->required()
                ->rules('required|string|min:2|max:50|unique:inventory_items,code,{{id}}|regex:/^[A-Z0-9_]+$/')
                ->help('Unique code (2-50 characters, uppercase letters, numbers, underscore only)')
                ->placeholder('e.g., ITEM001, RAW_SUGAR');

            // Item name
            $form->text('name', 'Item Name')
                ->required()
                ->rules('required|string|min:3|max:255')
                ->help('Full item name (3-255 characters)')
                ->placeholder('e.g., Premium Sugar, Office Chair');

            // Description
            $form->textarea('description', 'Description')
                ->rules('nullable|string|max:500')
                ->rows(3)
                ->help('Brief description (max 500 characters)')
                ->placeholder('Enter item description...');

            // Category
            $form->select('category_id', 'Category')
                ->options(Category::active()->pluck('name', 'id'))
                ->rules('nullable|integer|exists:categories,id')
                ->help('Select item category');

            // Department
            $form->select('department_id', 'Department')
                ->options(Department::active()->pluck('name', 'id'))
                ->required()
                ->rules('required|integer|exists:departments,id')
                ->help('Select responsible department');

            // Unit of measure
            $form->text('unit_of_measure', 'Unit of Measure')
                ->required()
                ->rules('required|string|min:1|max:50')
                ->help('Unit of measure (e.g., kg, liter, piece, box)')
                ->placeholder('e.g., kg, pcs, ltr');
        });

        $form->tab('Stock Information', function ($form) {
            // Current quantity
            $form->decimal('quantity', 'Current Quantity')
                ->default(0)
                ->rules('required|numeric|min:0|max:999999999.99')
                ->help('Current stock quantity');

            // Minimum quantity
            $form->decimal('minimum_quantity', 'Minimum Quantity')
                ->rules('nullable|numeric|min:0|max:999999999.99')
                ->help('Minimum allowed quantity before alert');

            // Maximum quantity
            $form->decimal('maximum_quantity', 'Maximum Quantity')
                ->rules('nullable|numeric|min:0|max:999999999.99')
                ->help('Maximum allowed quantity');

            // Reorder point
            $form->decimal('reorder_point', 'Reorder Point')
                ->rules('nullable|numeric|min:0|max:999999999.99')
                ->help('Quantity at which to reorder');

            // Unit cost
            $form->currency('unit_cost', 'Unit Cost')
                ->symbol('ETB')
                ->rules('nullable|numeric|min:0|max:99999999.99')
                ->help('Cost per unit');

            // Status (readonly as it's auto-calculated)
            $form->select('status', 'Status')
                ->options([
                    'in_stock' => 'In Stock',
                    'low_stock' => 'Low Stock',
                    'out_of_stock' => 'Out of Stock',
                    'discontinued' => 'Discontinued'
                ])
                ->default('in_stock')
                ->rules('required|in:in_stock,low_stock,out_of_stock,discontinued')
                ->help('Current stock status');
        });

        $form->tab('Additional Information', function ($form) {
            // Notes
            $form->textarea('notes', 'Notes')
                ->rules('nullable|string|max:1000')
                ->rows(4)
                ->help('Additional notes');

            // Active status
            $form->switch('is_active', 'Active')
                ->default(1)
                ->help('Enable or disable this item');

            // Display audit info in edit mode
            if ($form->isEditing()) {
                $form->divider();
                $form->display('created_at', 'Created At');
                $form->display('updated_at', 'Updated At');
            }
        });

        // Set audit fields
        $form->saving(function (Form $form) {
            $userId = auth()->user()->id;
            
            // Check if user exists
            if (User::find($userId)) {
                if ($form->isCreating()) {
                    $form->created_by = $userId;
                    $form->updated_by = $userId;
                }
                if ($form->isEditing()) {
                    $form->updated_by = $userId;
                }
            }
        });

        // Auto-update status based on quantity
        $form->saved(function (Form $form) {
            $item = $form->model();
            $item->updateStatus();
        });

        return $form;
    }
}