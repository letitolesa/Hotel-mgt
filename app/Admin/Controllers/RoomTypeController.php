<?php

namespace App\Admin\Controllers;

use App\Models\RoomType;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class RoomTypeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Room Type Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RoomType());

        $grid->column('id', 'ID')->sortable();
        $grid->column('code', 'Code')->sortable()->badge('primary');
        $grid->column('name', 'Name')->sortable();
        $grid->column('description', 'Description')->display(function ($desc) {
            return $desc ? substr($desc, 0, 50) . (strlen($desc) > 50 ? '...' : '') : '-';
        });
        $grid->column('base_price', 'Base Price')->display(function ($price) {
            return 'ETB ' . number_format($price, 2);
        })->sortable();
        $grid->column('max_occupancy', 'Max Occupancy')->sortable();
        $grid->column('bed_type', 'Bed Type')->sortable();
        $grid->column('is_active', 'Status')->display(function ($status) {
            return $status ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>';
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('code', 'Code');
            $filter->like('name', 'Name');
            $filter->like('bed_type', 'Bed Type');
            $filter->equal('is_active', 'Status')->select([
                '' => 'All',
                1 => 'Active',
                0 => 'Inactive'
            ]);
        });

        // Quick search
        $grid->quickSearch('code', 'name', 'bed_type');

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
        $show = new Show(RoomType::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('code', 'Code');
        $show->field('name', 'Name');
        $show->field('description', 'Description');
        $show->field('base_price', 'Base Price')->as(function ($price) {
            return 'ETB ' . number_format($price, 2);
        });
        $show->field('max_occupancy', 'Max Occupancy');
        $show->field('size_sq_meters', 'Size (sq meters)');
        $show->field('bed_type', 'Bed Type');
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
        $form = new Form(new RoomType());

        // Room code
        $form->text('code', 'Room Code')
            ->required()
            ->rules('required|string|min:2|max:20|unique:room_types,code,{{id}}|regex:/^[A-Z0-9_]+$/')
            ->help('Unique code (2-20 characters, uppercase letters, numbers, underscore only)')
            ->placeholder('e.g., STD, DLX, STE');

        // Room name
        $form->text('name', 'Room Type Name')
            ->required()
            ->rules('required|string|min:3|max:100')
            ->help('Full room type name (3-100 characters)')
            ->placeholder('e.g., Standard Room, Deluxe Suite');

        // Description
        $form->textarea('description', 'Description')
            ->rules('nullable|string|max:1000')
            ->rows(3)
            ->help('Brief description')
            ->placeholder('Enter room description...');

        // Base price
        $form->currency('base_price', 'Base Price (per night)')
            ->symbol('ETB')
            ->required()
            ->rules('required|numeric|min:0|max:999999.99')
            ->help('Base price per night')
            ->placeholder('0.00');

        // Max occupancy
        $form->number('max_occupancy', 'Maximum Occupancy')
            ->required()
            ->rules('required|integer|min:1|max:20')
            ->help('Maximum number of guests')
            ->default(2);

        // Room size
        $form->number('size_sq_meters', 'Room Size (m²)')
            ->rules('nullable|integer|min:1|max:1000')
            ->help('Room size in square meters')
            ->placeholder('e.g., 25');

        // Bed type
        $form->select('bed_type', 'Bed Type')
            ->options([
                'single' => 'Single Bed',
                'double' => 'Double Bed',
                'queen' => 'Queen Size',
                'king' => 'King Size',
                'twin' => 'Twin Beds',
                'bunk' => 'Bunk Beds',
                'sofa_bed' => 'Sofa Bed'
            ])
            ->required()
            ->rules('required|string|max:50')
            ->help('Type of bed(s)')
            ->default('double');

        // Active status
        $form->switch('is_active', 'Active')
            ->default(1)
            ->help('Enable/disable this room type');

        // Display timestamps in edit mode
        if ($form->isEditing()) {
            $form->divider();
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        }

        return $form;
    }
}