<?php

namespace App\Admin\Controllers;

use App\Models\HotelTable;
use App\Models\Section;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class HotelTableController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Table Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new HotelTable());

        $grid->column('id', 'ID')->sortable();
        $grid->column('table_number', 'Table #')->sortable()->badge('primary');
        $grid->column('table_name', 'Table Name')->sortable()->display(function ($name) {
            return $name ?: '-';
        });
        $grid->column('section.name', 'Section')->sortable();
        $grid->column('capacity', 'Capacity')->sortable();
        $grid->column('shape', 'Shape')->display(function ($shape) {
            $shapes = [
                'round' => 'Round',
                'square' => 'Square',
                'rectangle' => 'Rectangle',
                'booth' => 'Booth'
            ];
            return $shapes[$shape] ?? ucfirst($shape);
        });
        $grid->column('status', 'Status')->display(function ($status) {
            $colors = [
                'available' => 'success',
                'occupied' => 'danger',
                'reserved' => 'info',
                'cleaning' => 'warning',
                'maintenance' => 'default'
            ];
            $labels = [
                'available' => 'Available',
                'occupied' => 'Occupied',
                'reserved' => 'Reserved',
                'cleaning' => 'Cleaning',
                'maintenance' => 'Maintenance'
            ];
            $color = $colors[$status] ?? 'default';
            $label = $labels[$status] ?? ucfirst($status);
            return "<span class='label label-{$color}'>" . $label . "</span>";
        });
        $grid->column('cleaning_status', 'Cleaning')->display(function ($status) {
            $colors = [
                'clean' => 'success',
                'dirty' => 'danger',
                'in_progress' => 'warning'
            ];
            $labels = [
                'clean' => 'Clean',
                'dirty' => 'Dirty',
                'in_progress' => 'In Progress'
            ];
            $color = $colors[$status] ?? 'default';
            $label = $labels[$status] ?? ucfirst($status);
            return "<span class='label label-{$color}'>" . $label . "</span>";
        });
        $grid->column('features', 'Features')->display(function () {
            $features = [];
            if ($this->is_accessible) $features[] = 'Accessible';
            if ($this->is_private) $features[] = 'Private';
            if ($this->has_view) $features[] = 'View';
            return empty($features) ? '-' : implode(', ', $features);
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('table_number', 'Table Number');
            $filter->like('table_name', 'Table Name');
            $filter->equal('section_id', 'Section')->select(Section::active()->pluck('name', 'id'));
            $filter->equal('capacity', 'Min Capacity')->select([
                '' => 'Any',
                2 => '2+',
                4 => '4+',
                6 => '6+',
                8 => '8+',
                10 => '10+'
            ]);
            $filter->equal('shape', 'Shape')->select([
                '' => 'All',
                'round' => 'Round',
                'square' => 'Square',
                'rectangle' => 'Rectangle',
                'booth' => 'Booth'
            ]);
            $filter->equal('status', 'Status')->select([
                '' => 'All',
                'available' => 'Available',
                'occupied' => 'Occupied',
                'reserved' => 'Reserved',
                'cleaning' => 'Cleaning',
                'maintenance' => 'Maintenance'
            ]);
            $filter->equal('cleaning_status', 'Cleaning Status')->select([
                '' => 'All',
                'clean' => 'Clean',
                'dirty' => 'Dirty',
                'in_progress' => 'In Progress'
            ]);
            $filter->equal('is_accessible', 'Accessible')->select([
                '' => 'All',
                1 => 'Yes',
                0 => 'No'
            ]);
            $filter->equal('is_private', 'Private')->select([
                '' => 'All',
                1 => 'Yes',
                0 => 'No'
            ]);
        });

        // Quick search
        $grid->quickSearch('table_number', 'table_name');

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
        $show = new Show(HotelTable::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('table_number', 'Table Number');
        $show->field('table_name', 'Table Name');
        $show->field('section.name', 'Section');
        $show->field('capacity', 'Capacity');
        $show->field('minimum_capacity', 'Minimum Capacity');
        $show->field('shape', 'Shape')->using([
            'round' => 'Round',
            'square' => 'Square',
            'rectangle' => 'Rectangle',
            'booth' => 'Booth'
        ]);
        
        $show->field('position_x', 'Position X');
        $show->field('position_y', 'Position Y');
        $show->field('width', 'Width');
        $show->field('height', 'Height');
        
        $show->field('is_accessible', 'Accessible')->as(function ($value) {
            return $value ? 'Yes' : 'No';
        });
        $show->field('is_private', 'Private')->as(function ($value) {
            return $value ? 'Yes' : 'No';
        });
        $show->field('has_view', 'Has View')->as(function ($value) {
            return $value ? 'Yes' : 'No';
        });
        
        $show->field('status', 'Status')->using([
            'available' => 'Available',
            'occupied' => 'Occupied',
            'reserved' => 'Reserved',
            'cleaning' => 'Cleaning',
            'maintenance' => 'Maintenance'
        ]);
        $show->field('cleaning_status', 'Cleaning Status')->using([
            'clean' => 'Clean',
            'dirty' => 'Dirty',
            'in_progress' => 'In Progress'
        ]);
        $show->field('last_cleaned_at', 'Last Cleaned At');
        $show->field('notes', 'Notes');
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
        $form = new Form(new HotelTable());

        // Table Number
        $form->text('table_number', 'Table Number')
            ->required()
            ->rules('required|string|min:1|max:10|unique:hotel_tables,table_number,{{id}}|regex:/^[A-Z0-9-]+$/')
            ->help('Unique table number (1-10 characters, uppercase letters, numbers, hyphen only)')
            ->placeholder('e.g., T1, 101, A-5');

        // Table Name
        $form->text('table_name', 'Table Name')
            ->rules('nullable|string|max:50')
            ->help('Optional friendly name for the table')
            ->placeholder('e.g., Window Seat, VIP Corner');

        // Section
        $form->select('section_id', 'Section')
            ->options(Section::active()->pluck('name', 'id'))
            ->required()
            ->rules('required|integer|exists:sections,id')
            ->help('Select the section where this table is located');

        // Capacity
        $form->divider('Capacity');
        
        $form->number('capacity', 'Capacity')
            ->required()
            ->rules('required|integer|min:1|max:20')
            ->help('Maximum number of people this table can accommodate')
            ->default(2);

        $form->number('minimum_capacity', 'Minimum Capacity')
            ->rules('nullable|integer|min:1|max:20')
            ->help('Minimum number of people required (optional)')
            ->placeholder('e.g., 2');

        // Table Shape and Dimensions
        $form->divider('Shape & Dimensions');
        
        $form->select('shape', 'Table Shape')
            ->options([
                'round' => 'Round',
                'square' => 'Square',
                'rectangle' => 'Rectangle',
                'booth' => 'Booth'
            ])
            ->default('rectangle')
            ->required()
            ->rules('required|in:round,square,rectangle,booth')
            ->help('Shape of the table');

        $form->number('width', 'Width')
            ->rules('nullable|integer|min:1|max:1000')
            ->help('Width in pixels/units (for floor plan)')
            ->placeholder('e.g., 100');

        $form->number('height', 'Height')
            ->rules('nullable|integer|min:1|max:1000')
            ->help('Height in pixels/units (for floor plan)')
            ->placeholder('e.g., 100');

        // Position (for floor plan)
        $form->divider('Position (Floor Plan)');
        
        $form->number('position_x', 'X Position')
            ->rules('nullable|integer|min:0|max:5000')
            ->help('X coordinate for floor plan')
            ->placeholder('e.g., 100');

        $form->number('position_y', 'Y Position')
            ->rules('nullable|integer|min:0|max:5000')
            ->help('Y coordinate for floor plan')
            ->placeholder('e.g., 200');

        // Table Features
        $form->divider('Table Features');
        
        $form->switch('is_accessible', 'Accessible')
            ->default(0)
            ->help('Wheelchair accessible table');

        $form->switch('is_private', 'Private')
            ->default(0)
            ->help('Private/separated table');

        $form->switch('has_view', 'Has View')
            ->default(0)
            ->help('Table with a view');

        // Status Management
        $form->divider('Status');
        
        $form->select('status', 'Status')
            ->options([
                'available' => 'Available',
                'occupied' => 'Occupied',
                'reserved' => 'Reserved',
                'cleaning' => 'Cleaning',
                'maintenance' => 'Maintenance'
            ])
            ->default('available')
            ->required()
            ->rules('required|in:available,occupied,reserved,cleaning,maintenance')
            ->help('Current table status');

 

  

        // Notes
        $form->divider('Additional Information');
        
        $form->textarea('notes', 'Notes')
            ->rules('nullable|string|max:500')
            ->rows(3)
            ->help('Additional notes about the table')
            ->placeholder('Enter any special notes or instructions...');

        // Display timestamps in edit mode
        if ($form->isEditing()) {
            $form->divider();
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        }

        // Validation to ensure minimum_capacity <= capacity
        $form->saving(function (Form $form) {
            if ($form->minimum_capacity && $form->capacity) {
                if ($form->minimum_capacity > $form->capacity) {
                    throw new \Exception('Minimum capacity cannot be greater than maximum capacity.');
                }
            }
        });

        return $form;
    }
}