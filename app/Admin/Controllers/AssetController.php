<?php

namespace App\Admin\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class AssetController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Asset Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Asset());

        $grid->column('id', 'ID')->sortable();
        $grid->column('asset_tag', 'Asset Tag')->sortable()->badge('primary');
        $grid->column('name', 'Asset Name')->sortable();
        $grid->column('category.name', 'Category')->sortable();
        $grid->column('department.name', 'Department')->sortable();
        $grid->column('model', 'Model')->sortable();
        $grid->column('is_active', 'Active')->display(function ($active) {
            return $active ? '<span class="label label-success">Yes</span>' : '<span class="label label-danger">No</span>';
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('asset_tag', 'Asset Tag');
            $filter->like('name', 'Name');
            $filter->like('serial_number', 'Serial Number');
            $filter->like('model', 'Model');
            $filter->equal('category_id', 'Category')->select(Category::active()->pluck('name', 'id'));
            $filter->equal('department_id', 'Department')->select(Department::active()->pluck('name', 'id'));
            $filter->equal('status', 'Status')->select([
                '' => 'All',
                'available' => 'Available',
                'assigned' => 'Assigned',
                'maintenance' => 'Maintenance',
                'retired' => 'Retired',
                'lost' => 'Lost'
            ]);
            $filter->equal('condition', 'Condition')->select([
                '' => 'All',
                'new' => 'New',
                'good' => 'Good',
                'fair' => 'Fair',
                'poor' => 'Poor',
                'damaged' => 'Damaged'
            ]);
            $filter->equal('is_active', 'Active')->select([
                '' => 'All',
                1 => 'Yes',
                0 => 'No'
            ]);
            $filter->between('purchase_date', 'Purchase Date')->date();
        });

        // Quick search
        $grid->quickSearch('asset_tag', 'name', 'serial_number', 'model');

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
        $show = new Show(Asset::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('asset_tag', 'Asset Tag');
        $show->field('name', 'Asset Name');
        $show->field('description', 'Description');
        $show->field('category.name', 'Category');
        $show->field('department.name', 'Department');
        $show->field('model', 'Model');
        $show->field('serial_number', 'Serial Number');
        $show->field('manufacturer', 'Manufacturer');
        $show->field('purchase_date', 'Purchase Date');
        $show->field('purchase_cost', 'Purchase Cost')->as(function ($cost) {
            return $cost ? 'ETB ' . number_format($cost, 2) : 'Not Set';
        });
        $show->field('current_value', 'Current Value')->as(function ($value) {
            return $value ? 'ETB ' . number_format($value, 2) : 'Not Set';
        });
        $show->field('warranty_expiry', 'Warranty Expiry');
        $show->field('status', 'Status')->using([
            'available' => 'Available',
            'assigned' => 'Assigned',
            'maintenance' => 'Maintenance',
            'retired' => 'Retired',
            'lost' => 'Lost'
        ]);
        $show->field('condition', 'Condition')->using([
            'new' => 'New',
            'good' => 'Good',
            'fair' => 'Fair',
            'poor' => 'Poor',
            'damaged' => 'Damaged'
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
        $form = new Form(new Asset());

        $form->tab('Basic Information', function ($form) {
            // Asset Tag
            $form->text('asset_tag', 'Asset Tag')
                ->required()
                ->rules('required|string|min:3|max:50|unique:assets,asset_tag,{{id}}|regex:/^[A-Z0-9_-]+$/')
                ->help('Unique asset tag (3-50 characters, uppercase letters, numbers, underscore, hyphen only)')
                ->placeholder('e.g., AST-001, LAP-DELL-001');

            // Asset Name
            $form->text('name', 'Asset Name')
                ->required()
                ->rules('required|string|min:3|max:255')
                ->help('Full asset name (3-255 characters)')
                ->placeholder('e.g., Dell Latitude Laptop, Office Chair');

            // Description
            $form->textarea('description', 'Description')
                ->rules('nullable|string|max:1000')
                ->rows(3)
                ->help('Brief description (max 1000 characters)')
                ->placeholder('Enter asset description...');

            // Category
            $form->select('category_id', 'Category')
                ->options(Category::active()->pluck('name', 'id'))
                ->rules('nullable|integer|exists:categories,id')
                ->help('Select asset category');

            // Department
            $form->select('department_id', 'Department')
                ->options(Department::active()->pluck('name', 'id'))
                ->required()
                ->rules('required|integer|exists:departments,id')
                ->help('Select responsible department');
        });

        $form->tab('Technical Details', function ($form) {
            // Model
            $form->text('model', 'Model')
                ->rules('nullable|string|max:100')
                ->help('Asset model/number')
                ->placeholder('e.g., Latitude 5420, XPS 15');

            // Serial Number
            $form->text('serial_number', 'Serial Number')
                ->rules('nullable|string|max:100|unique:assets,serial_number,{{id}}')
                ->help('Unique serial number (if available)')
                ->placeholder('e.g., SN123456789');

            // Manufacturer
            $form->text('manufacturer', 'Manufacturer')
                ->rules('nullable|string|max:100')
                ->help('Manufacturer/brand name')
                ->placeholder('e.g., Dell, HP, Herman Miller');
        });

        $form->tab('Purchase Information', function ($form) {
            // Purchase Date
            $form->date('purchase_date', 'Purchase Date')
                ->rules('nullable|date|before_or_equal:today')
                ->help('Date when asset was purchased');

            // Purchase Cost
            $form->currency('purchase_cost', 'Purchase Cost')
                ->symbol('ETB')
                ->rules('nullable|numeric|min:0|max:999999999999.99')
                ->help('Original purchase cost');

            // Current Value
            $form->currency('current_value', 'Current Value')
                ->symbol('ETB')
                ->rules('nullable|numeric|min:0|max:999999999999.99')
                ->help('Current depreciated value');

            // Warranty Expiry
            $form->date('warranty_expiry', 'Warranty Expiry')
                ->rules('nullable|date|after_or_equal:purchase_date')
                ->help('Warranty expiration date');
        });

        $form->tab('Status & Notes', function ($form) {
            // Status
            $form->select('status', 'Status')
                ->options([
                    'available' => 'Available',
                    'assigned' => 'Assigned',
                    'maintenance' => 'Maintenance',
                    'retired' => 'Retired',
                    'lost' => 'Lost'
                ])
                ->default('available')
                ->required()
                ->rules('required|in:available,assigned,maintenance,retired,lost')
                ->help('Current asset status');

            // Condition
            $form->select('condition', 'Condition')
                ->options([
                    'new' => 'New',
                    'good' => 'Good',
                    'fair' => 'Fair',
                    'poor' => 'Poor',
                    'damaged' => 'Damaged'
                ])
                ->default('good')
                ->required()
                ->rules('required|in:new,good,fair,poor,damaged')
                ->help('Physical condition of the asset');

            // Notes
            $form->textarea('notes', 'Notes')
                ->rules('nullable|string|max:1000')
                ->rows(4)
                ->help('Additional notes about the asset');

            // Active status
            $form->switch('is_active', 'Active')
                ->default(1)
                ->help('Enable or disable this asset');
        });

        $form->tab('Audit Information', function ($form) {
            // Display audit info in edit mode
            if ($form->isEditing()) {
                $form->display('created_at', 'Created At');
                $form->display('updated_at', 'Updated At');
                $form->display('created_by', 'Created By')->with(function ($value) {
                    return $value ? User::find($value)->name : 'System';
                });
                $form->display('updated_by', 'Updated By')->with(function ($value) {
                    return $value ? User::find($value)->name : 'System';
                });
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

        return $form;
    }
}