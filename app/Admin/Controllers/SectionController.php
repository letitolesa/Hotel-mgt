<?php

namespace App\Admin\Controllers;

use App\Models\Section;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class SectionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Section Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Section());

        $grid->column('id', 'ID')->sortable();
        $grid->column('code', 'Code')->sortable()->badge('primary');
        $grid->column('name', 'Name')->sortable();
        $grid->column('location', 'Location')->sortable();
        $grid->column('image', 'Image')->display(function () {
            if ($this->image_url) {
                return '<img src="' . asset('storage/' . $this->image_url) . '" style="max-width: 50px; max-height: 50px; border-radius: 4px;">';
            }
            return '<span class="text-muted">No image</span>';
        });
        $grid->column('capacity', 'Capacity')->display(function () {
            $min = $this->min_capacity ?: 'Any';
            $max = $this->max_capacity ?: 'Any';
            return $min . ' - ' . $max;
        });
        $grid->column('features', 'Features')->display(function () {
            $features = [];
            if ($this->is_smoking) $features[] = 'Smoking';
            if ($this->is_outdoor) $features[] = 'Outdoor';
            if ($this->is_private) $features[] = 'Private';
            return empty($features) ? '-' : implode(', ', $features);
        });
        $grid->column('is_active', 'Status')->display(function ($status) {
            return $status ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>';
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('code', 'Code');
            $filter->like('name', 'Name');
            $filter->like('location', 'Location');
            $filter->equal('is_smoking', 'Smoking')->select([
                '' => 'All',
                1 => 'Yes',
                0 => 'No'
            ]);
            $filter->equal('is_outdoor', 'Outdoor')->select([
                '' => 'All',
                1 => 'Yes',
                0 => 'No'
            ]);
            $filter->equal('is_private', 'Private')->select([
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
        $grid->quickSearch('code', 'name', 'location');

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
        $show = new Show(Section::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('code', 'Code');
        $show->field('name', 'Name');
        $show->field('description', 'Description');
        $show->field('location', 'Location');
        
        // Display image
        $show->field('image_url', 'Image')->unescape()->as(function ($image) {
            if ($image) {
                return '<img src="' . asset('storage/' . $image) . '" style="max-width: 300px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
            }
            return '<span class="text-muted">No image uploaded</span>';
        });
        
        $show->field('is_smoking', 'Smoking Allowed')->as(function ($value) {
            return $value ? 'Yes' : 'No';
        });
        $show->field('is_outdoor', 'Outdoor Section')->as(function ($value) {
            return $value ? 'Yes' : 'No';
        });
        $show->field('is_private', 'Private Section')->as(function ($value) {
            return $value ? 'Yes' : 'No';
        });
        $show->field('min_capacity', 'Minimum Capacity');
        $show->field('max_capacity', 'Maximum Capacity');
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
        $form = new Form(new Section());

        // Code
        $form->text('code', 'Section Code')
            ->required()
            ->rules('required|string|min:2|max:20|unique:sections,code,{{id}}|regex:/^[A-Z0-9_]+$/')
            ->help('Unique code (2-20 characters, uppercase letters, numbers, underscore only)')
            ->placeholder('e.g., MAIN, PATIO, VIP');

        // Name
        $form->text('name', 'Section Name')
            ->required()
            ->rules('required|string|min:3|max:100')
            ->help('Full section name (3-100 characters)')
            ->placeholder('e.g., Main Dining, Patio, VIP Lounge');

        // Description
        $form->textarea('description', 'Description')
            ->rules('nullable|string|max:500')
            ->rows(3)
            ->help('Brief description (max 500 characters)')
            ->placeholder('Enter section description...');

        // Location
        $form->text('location', 'Location')
            ->rules('nullable|string|max:100')
            ->help('Physical location within the restaurant')
            ->placeholder('e.g., Ground Floor, Rooftop, Garden');

        // Section Features
        $form->divider('Section Features');

        $form->switch('is_smoking', 'Smoking Allowed')
            ->default(0)
            ->help('Allow smoking in this section');

        $form->switch('is_outdoor', 'Outdoor Section')
            ->default(0)
            ->help('This is an outdoor section');

        $form->switch('is_private', 'Private Section')
            ->default(0)
            ->help('This is a private section');

        // Capacity
        $form->divider('Capacity');

        $form->number('min_capacity', 'Minimum Capacity')
            ->rules('nullable|integer|min:1|max:1000')
            ->help('Minimum number of people this section can accommodate')
            ->placeholder('e.g., 2');

        $form->number('max_capacity', 'Maximum Capacity')
            ->rules('nullable|integer|min:1|max:1000')
            ->help('Maximum number of people this section can accommodate')
            ->placeholder('e.g., 50');

        // Image Upload
        $form->divider('Section Image');
        
        $form->image('image_url', 'Section Image')
            ->uniqueName()
            ->move('sections', date('Y/m/d'))
            ->removable()
            ->downloadable()
            ->help('Upload section image (JPG, PNG, GIF up to 2MB)')
            ->rules('nullable|image|mimes:jpeg,png,jpg,gif|max:2048');

        // Active Status
        $form->divider('Status');
        
        $form->switch('is_active', 'Active')
            ->default(1)
            ->help('Enable or disable this section');

        // Display timestamps in edit mode
        if ($form->isEditing()) {
            $form->divider();
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
            
            // Display current image
            if ($form->model()->image_url) {
                $form->html(function () use ($form) {
                    return '<div class="form-group">
                        <label class="col-sm-2 control-label">Current Image</label>
                        <div class="col-sm-8">
                            <div class="box box-solid" style="border: 1px solid #d2d6de; padding: 10px; border-radius: 4px;">
                                <img src="' . asset('storage/' . $form->model()->image_url) . '" style="max-width: 100%; max-height: 300px; border-radius: 4px;">
                                <p class="help-block" style="margin-top: 10px;">Current image: ' . basename($form->model()->image_url) . '</p>
                            </div>
                        </div>
                    </div>';
                }, 'Current Image');
            }
        }

        // Validation to ensure max_capacity >= min_capacity
        $form->saving(function (Form $form) {
            if ($form->min_capacity && $form->max_capacity) {
                if ($form->min_capacity > $form->max_capacity) {
                    throw new \Exception('Maximum capacity must be greater than or equal to minimum capacity.');
                }
            }
        });

        return $form;
    }
}