<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class CategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Category Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Category());

        $grid->column('id', 'ID')->sortable();
        $grid->column('code', 'Category Code')->sortable();
        $grid->column('name', 'Category Name')->sortable();
        $grid->column('full_path', 'Full Path')->display(function () {
            return $this->full_path;
        });
        $grid->column('parent.name', 'Parent Category')->display(function ($parent) {
            return $parent ?: '<span class="label label-default">None (Root)</span>';
        });
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
            $filter->equal('parent_id', 'Parent Category')->select(
                ['' => 'None (Root)'] + Category::active()->pluck('name', 'id')->toArray()
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
        $show = new Show(Category::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('code', 'Category Code');
        $show->field('name', 'Category Name');
        $show->field('full_path', 'Full Path');
        $show->field('parent.name', 'Parent Category')->default('None (Root)');
        $show->field('description', 'Description');
        $show->field('is_active', 'Status')->using([1 => 'Active', 0 => 'Inactive']);
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        // Show child categories
        $show->children('Sub-categories', function ($children) {
            $children->resource('/admin/categories');
            $children->code();
            $children->name();
            $children->is_active()->switch();
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Category());

        // Category code
        $form->text('code', 'Category Code')
            ->required()
            ->rules('required|string|min:2|max:50|unique:categories,code,{{id}}|regex:/^[A-Z0-9_]+$/')
            ->help('Unique code (2-50 characters, uppercase letters, numbers, underscore only)')
            ->placeholder('e.g., ELEC, FURN, OFF_SUP');

        // Category name
        $form->text('name', 'Category Name')
            ->required()
            ->rules('required|string|min:3|max:255')
            ->help('Full category name (3-255 characters)')
            ->placeholder('e.g., Electronics, Furniture, Office Supplies');

        // Parent category (nullable for root categories)
        $form->select('parent_id', 'Parent Category')
            ->options(function ($id) {
                // Get all active categories
                $categories = Category::active();
                
                // If editing, exclude the current category and its children to prevent circular references
                if ($id) {
                    $current = Category::find($id);
                    $excludeIds = [$id];
                    
                    // Add all descendants to exclude
                    if ($current) {
                        $excludeIds = array_merge($excludeIds, $current->children()->pluck('id')->toArray());
                    }
                    
                    $categories = $categories->whereNotIn('id', $excludeIds);
                }
                
                return ['' => 'None (Root Category)'] + $categories->pluck('name', 'id')->toArray();
            })
            ->rules('nullable|integer|exists:categories,id')
            ->help('Select a parent category if this is a sub-category. Leave empty for root category.');

        // Description
        $form->textarea('description', 'Description')
            ->rules('nullable|string|max:500')
            ->rows(3)
            ->help('Brief description (max 500 characters)')
            ->placeholder('Enter category description...');

        // Status
        $form->switch('is_active', 'Active')
            ->default(1)
            ->help('Enable or disable this category');

        // Display timestamps in edit form
        if ($form->isEditing()) {
            $form->divider();
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        }

        // Validation to prevent circular references and self-parenting
        $form->saving(function (Form $form) {
            $parentId = $form->parent_id;
            $currentId = $form->model()->id;
            
            // Only check self-parenting if we have a current ID (editing mode)
            if ($currentId && $parentId == $currentId) {
                throw new \Exception('A category cannot be its own parent.');
            }
            
            // Check for circular reference (if parent is a child of current category)
            if ($parentId && $currentId) {
                $parent = Category::find($parentId);
                
                // Traverse up the parent chain to check if current category becomes an ancestor
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