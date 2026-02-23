<?php

namespace App\Admin\Controllers;

use App\Models\MenuCategory;
use App\Models\Department;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class MenuCategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Menu Category Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MenuCategory());

        $grid->column('id', 'ID')->sortable();
        $grid->column('code', 'Category Code')->sortable()->badge('primary');
        $grid->column('name', 'Category Name')->sortable();
        $grid->column('full_path', 'Full Path')->display(function () {
            return $this->full_path;
        });
        $grid->column('department.name', 'Department')->sortable();
        $grid->column('parent.name', 'Parent Category')->display(function ($parent) {
            return $parent ?: '<span class="label label-default">Root</span>';
        });
        $grid->column('sort_order', 'Sort Order')->sortable()->badge('info');
        $grid->column('image', 'Image')->display(function () {
            if ($this->image_path) {
                return '<img src="' . asset('storage/' . $this->image_path) . '" style="max-width: 40px; max-height: 40px; border-radius: 4px;">';
            }
            return '<span class="text-muted">No image</span>';
        });
        $grid->column('is_active', 'Status')->display(function ($status) {
            return $status ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>';
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('code', 'Code');
            $filter->like('name', 'Name');
            $filter->equal('department_id', 'Department')->select(Department::active()->pluck('name', 'id'));
            $filter->equal('parent_id', 'Parent Category')->select(
                ['' => 'None (Root)'] + MenuCategory::active()->pluck('name', 'id')->toArray()
            );
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
        $show = new Show(MenuCategory::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('code', 'Category Code');
        $show->field('name', 'Category Name');
        $show->field('full_path', 'Full Path');
        $show->field('description', 'Description');
        $show->field('department.name', 'Department');
        $show->field('parent.name', 'Parent Category')->default('None (Root)');
        $show->field('sort_order', 'Sort Order');
        
        // Display image
        $show->field('image_path', 'Image')->unescape()->as(function ($image) {
            if ($image) {
                return '<img src="' . asset('storage/' . $image) . '" style="max-width: 300px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
            }
            return '<span class="text-muted">No image uploaded</span>';
        });
        
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
        $form = new Form(new MenuCategory());

        // Category Code
        $form->text('code', 'Category Code')
            ->required()
            ->rules('required|string|min:2|max:20|unique:menu_categories,code,{{id}}|regex:/^[A-Z0-9_]+$/')
            ->help('Unique code (2-20 characters, uppercase letters, numbers, underscore only)')
            ->placeholder('e.g., APP, MAIN, DES, BEV');

        // Category Name
        $form->text('name', 'Category Name')
            ->required()
            ->rules('required|string|min:3|max:100')
            ->help('Full category name (3-100 characters)')
            ->placeholder('e.g., Appetizers, Main Course, Desserts');

        // Description
        $form->textarea('description', 'Description')
            ->rules('nullable|string|max:500')
            ->rows(3)
            ->help('Brief description (max 500 characters)')
            ->placeholder('Enter category description...');

        // Department
        $form->select('department_id', 'Department')
            ->options(Department::active()->pluck('name', 'id'))
            ->required()
            ->rules('required|integer|exists:departments,id')
            ->help('Select the department this menu category belongs to');

        // Parent Category (nullable for root categories)
        $form->select('parent_id', 'Parent Category')
            ->options(function ($id) {
                // Get all active categories except current one and its children
                $categories = MenuCategory::active();
                
                if ($id) {
                    $current = MenuCategory::find($id);
                    $excludeIds = [$id];
                    
                    if ($current) {
                        $excludeIds = array_merge($excludeIds, $current->children()->pluck('id')->toArray());
                    }
                    
                    $categories = $categories->whereNotIn('id', $excludeIds);
                }
                
                return ['' => 'None (Root Category)'] + $categories->pluck('name', 'id')->toArray();
            })
            ->rules('nullable|integer|exists:menu_categories,id')
            ->help('Select a parent category if this is a sub-category. Leave empty for root category.');

        // Sort Order
        $form->number('sort_order', 'Sort Order')
            ->default(0)
            ->rules('required|integer|min:0|max:999')
            ->help('Display order (lower numbers appear first)')
            ->placeholder('e.g., 0, 10, 20');

        // Image Upload
        $form->divider('Category Image');
        
        $form->image('image_path', 'Category Image')
            ->uniqueName()
            ->move('menu_categories', date('Y/m/d'))
            ->removable()
            ->downloadable()
            ->help('Upload category image (JPG, PNG, GIF up to 2MB)')
            ->rules('nullable|image|mimes:jpeg,png,jpg,gif|max:2048');

        // Active Status
        $form->divider('Status');
        
        $form->switch('is_active', 'Active')
            ->default(1)
            ->help('Enable or disable this menu category');

        // Display current image in edit mode
        if ($form->isEditing()) {
            $form->divider();
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
            
            if ($form->model()->image_path) {
                $form->html(function () use ($form) {
                    return '<div class="form-group">
                        <label class="col-sm-2 control-label">Current Image</label>
                        <div class="col-sm-8">
                            <div class="box box-solid" style="border: 1px solid #d2d6de; padding: 10px; border-radius: 4px;">
                                <img src="' . asset('storage/' . $form->model()->image_path) . '" style="max-width: 100%; max-height: 200px; border-radius: 4px;">
                                <p class="help-block" style="margin-top: 10px;">Current image: ' . basename($form->model()->image_path) . '</p>
                            </div>
                        </div>
                    </div>';
                }, 'Current Image');
            }
        }

        // Prevent self-parenting and circular references
        $form->saving(function (Form $form) {
            $parentId = $form->parent_id;
            $currentId = $form->model()->id;
            
            // Check self-parenting
            if ($currentId && $parentId == $currentId) {
                throw new \Exception('A category cannot be its own parent.');
            }
            
            // Check for circular reference
            if ($parentId && $currentId) {
                $parent = MenuCategory::find($parentId);
                
                while ($parent) {
                    if ($parent->id == $currentId) {
                        throw new \Exception('Circular reference detected. A category cannot be a parent of its own ancestor.');
                    }
                    $parent = $parent->parent;
                }
            }
        });

        return $form;
    }
}