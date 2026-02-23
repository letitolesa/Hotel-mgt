<?php

namespace App\Admin\Controllers;

use App\Models\MenuItem;
use App\Models\MenuCategory;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class MenuItemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Menu Item Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MenuItem());

        $grid->column('id', 'ID')->sortable();
        $grid->column('code', 'Item Code')->sortable()->badge('primary');
        $grid->column('name', 'Item Name')->sortable();
        $grid->column('category.name', 'Category')->sortable();
        $grid->column('price', 'Price')->display(function ($price) {
            return 'ETB ' . number_format($price, 2);
        })->sortable();
        $grid->column('image', 'Image')->display(function () {
            if ($this->image_path) {
                return '<img src="' . asset('storage/' . $this->image_path) . '" style="max-width: 40px; max-height: 40px; border-radius: 4px;">';
            }
            return '<span class="text-muted">No image</span>';
        });
        $grid->column('preparation_time_minutes', 'Prep Time')->display(function ($time) {
            return $time ? $time . ' min' : '-';
        });
        $grid->column('features', 'Features')->display(function () {
            $features = [];
            if ($this->is_featured) $features[] = 'Featured';
            if ($this->is_taxable) $features[] = 'Taxable';
            return empty($features) ? '-' : implode(', ', $features);
        });
        $grid->column('is_available', 'Available')->display(function ($available) {
            return $available ? '<span class="label label-success">Yes</span>' : '<span class="label label-danger">No</span>';
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('code', 'Code');
            $filter->like('name', 'Name');
            $filter->equal('category_id', 'Category')->select(MenuCategory::active()->pluck('name', 'id'));
            $filter->between('price', 'Price Range');
            $filter->equal('is_available', 'Available')->select([
                '' => 'All',
                1 => 'Yes',
                0 => 'No'
            ]);
            $filter->equal('is_featured', 'Featured')->select([
                '' => 'All',
                1 => 'Yes',
                0 => 'No'
            ]);
            $filter->equal('is_taxable', 'Taxable')->select([
                '' => 'All',
                1 => 'Yes',
                0 => 'No'
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
        $show = new Show(MenuItem::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('ulid', 'ULID');
        $show->field('code', 'Item Code');
        $show->field('name', 'Item Name');
        $show->field('description', 'Description');
        $show->field('category.name', 'Category');
        $show->field('price', 'Price')->as(function ($price) {
            return 'ETB ' . number_format($price, 2);
        });
        $show->field('cost', 'Cost')->as(function ($cost) {
            return $cost ? 'ETB ' . number_format($cost, 2) : 'Not set';
        });
        
        // Display image
        $show->field('image_path', 'Image')->unescape()->as(function ($image) {
            if ($image) {
                return '<img src="' . asset('storage/' . $image) . '" style="max-width: 300px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
            }
            return '<span class="text-muted">No image uploaded</span>';
        });
        
        $show->field('preparation_time_minutes', 'Preparation Time')->as(function ($time) {
            return $time ? $time . ' minutes' : 'Not specified';
        });
        $show->field('allergens', 'Allergens')->as(function ($allergens) {
            return $allergens ?: 'None specified';
        });
        $show->field('is_taxable', 'Taxable')->using([1 => 'Yes', 0 => 'No']);
        $show->field('is_available', 'Available')->using([1 => 'Yes', 0 => 'No']);
        $show->field('is_featured', 'Featured')->using([1 => 'Yes', 0 => 'No']);
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
        $form = new Form(new MenuItem());

        // Item Code
        $form->text('code', 'Item Code')
            ->required()
            ->rules('required|string|min:2|max:20|unique:menu_items,code,{{id}}|regex:/^[A-Z0-9_]+$/')
            ->help('Unique code (2-20 characters, uppercase letters, numbers, underscore only)')
            ->placeholder('e.g., BURGER01, PASTA001');

        // Item Name
        $form->text('name', 'Item Name')
            ->required()
            ->rules('required|string|min:3|max:255')
            ->help('Full item name (3-255 characters)')
            ->placeholder('e.g., Classic Beef Burger, Spaghetti Carbonara');

        // Description
        $form->textarea('description', 'Description')
            ->rules('nullable|string|max:1000')
            ->rows(3)
            ->help('Brief description (max 1000 characters)')
            ->placeholder('Enter item description, ingredients, etc...');

        // Category
        $form->select('category_id', 'Category')
            ->options(MenuCategory::active()->pluck('name', 'id'))
            ->required()
            ->rules('required|integer|exists:menu_categories,id')
            ->help('Select the menu category');

        // Pricing
        $form->divider('Pricing');
        
        $form->currency('price', 'Selling Price')
            ->symbol('ETB')
            ->required()
            ->rules('required|numeric|min:0|max:999999.99')
            ->help('Selling price to customers')
            ->placeholder('0.00');

        $form->currency('cost', 'Cost Price')
            ->symbol('ETB')
            ->rules('nullable|numeric|min:0|max:999999.99')
            ->help('Cost price (for profit calculation)')
            ->placeholder('0.00');

        // Preparation
        $form->divider('Preparation');
        
        $form->number('preparation_time_minutes', 'Preparation Time (minutes)')
            ->rules('nullable|integer|min:1|max:480')
            ->help('Estimated preparation time in minutes')
            ->placeholder('e.g., 15');

        // Allergens
        $form->text('allergens', 'Allergens')
            ->rules('nullable|string|max:255')
            ->help('List of allergens (comma separated)')
            ->placeholder('e.g., gluten, dairy, nuts, eggs');

        // Image Upload
        $form->divider('Item Image');
        
        $form->image('image_path', 'Item Image')
            ->uniqueName()
            ->move('menu_items', date('Y/m/d'))
            ->removable()
            ->downloadable()
            ->help('Upload item image (JPG, PNG, GIF up to 2MB)')
            ->rules('nullable|image|mimes:jpeg,png,jpg,gif|max:2048');

        // Status Options
        $form->divider('Status');
        
        $form->switch('is_available', 'Available for Order')
            ->default(1)
            ->help('Item can be ordered by customers');

        $form->switch('is_featured', 'Featured Item')
            ->default(0)
            ->help('Show as featured/special item');

        $form->switch('is_taxable', 'Taxable')
            ->default(1)
            ->help('Tax applies to this item');

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

        return $form;
    }
}