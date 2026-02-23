<?php

namespace App\Admin\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;

class RoomController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Room Management';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Room());

        $grid->column('id', 'ID')->sortable();
        $grid->column('room_number', 'Room Number')->sortable()->badge('primary');
        $grid->column('roomType.name', 'Room Type')->sortable();
        $grid->column('floor', 'Floor')->sortable();
        $grid->column('wing', 'Wing')->sortable();
        $grid->column('status', 'Status')->display(function ($status) {
            $colors = [
                'available' => 'success',
                'occupied' => 'danger',
                'reserved' => 'info',
                'maintenance' => 'warning',
                'dirty' => 'default',
                'out_of_order' => 'default'
            ];
            $labels = [
                'available' => 'Available',
                'occupied' => 'Occupied',
                'reserved' => 'Reserved',
                'maintenance' => 'Maintenance',
                'dirty' => 'Dirty',
                'out_of_order' => 'Out of Order'
            ];
            $color = $colors[$status] ?? 'default';
            $label = $labels[$status] ?? ucfirst($status);
            return "<span class='label label-{$color}'>" . $label . "</span>";
        });
        $grid->column('housekeeping_status', 'Housekeeping')->display(function ($status) {
            $colors = [
                'clean' => 'success',
                'dirty' => 'danger',
                'inspected' => 'info',
                'out_of_service' => 'warning'
            ];
            $labels = [
                'clean' => 'Clean',
                'dirty' => 'Dirty',
                'inspected' => 'Inspected',
                'out_of_service' => 'Out of Service'
            ];
            $color = $colors[$status] ?? 'default';
            $label = $labels[$status] ?? ucfirst($status);
            return "<span class='label label-{$color}'>" . $label . "</span>";
        });
        $grid->column('last_cleaned_at', 'Last Cleaned')->display(function ($date) {
            if (!$date) {
                return '-';
            }
            // Handle both string and DateTime/Carbon objects
            if (is_string($date)) {
                return date('Y-m-d H:i', strtotime($date));
            }
            return $date->format('Y-m-d H:i');
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->like('room_number', 'Room Number');
            $filter->equal('room_type_id', 'Room Type')->select(RoomType::active()->pluck('name', 'id'));
            $filter->equal('floor', 'Floor');
            $filter->like('wing', 'Wing');
            $filter->equal('status', 'Status')->select([
                '' => 'All',
                'available' => 'Available',
                'occupied' => 'Occupied',
                'reserved' => 'Reserved',
                'maintenance' => 'Maintenance',
                'dirty' => 'Dirty',
                'out_of_order' => 'Out of Order'
            ]);
            $filter->equal('housekeeping_status', 'Housekeeping')->select([
                '' => 'All',
                'clean' => 'Clean',
                'dirty' => 'Dirty',
                'inspected' => 'Inspected',
                'out_of_service' => 'Out of Service'
            ]);
        });

        // Quick search
        $grid->quickSearch('room_number', 'wing');

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
        $show = new Show(Room::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('room_number', 'Room Number');
        $show->field('roomType.name', 'Room Type');
        $show->field('floor', 'Floor');
        $show->field('wing', 'Wing');
        $show->field('status', 'Status')->using([
            'available' => 'Available',
            'occupied' => 'Occupied',
            'reserved' => 'Reserved',
            'maintenance' => 'Maintenance',
            'dirty' => 'Dirty',
            'out_of_order' => 'Out of Order'
        ]);
        $show->field('housekeeping_status', 'Housekeeping Status')->using([
            'clean' => 'Clean',
            'dirty' => 'Dirty',
            'inspected' => 'Inspected',
            'out_of_service' => 'Out of Service'
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
        $form = new Form(new Room());

        // Room Number
        $form->text('room_number', 'Room Number')
            ->required()
            ->rules('required|string|min:1|max:10|unique:rooms,room_number,{{id}}|regex:/^[A-Z0-9-]+$/')
            ->help('Unique room number (1-10 characters, uppercase letters, numbers, hyphen only)')
            ->placeholder('e.g., 101, A-201, SUITE-1');

        // Room Type
        $form->select('room_type_id', 'Room Type')
            ->options(RoomType::active()->pluck('name', 'id'))
            ->required()
            ->rules('required|integer|exists:room_types,id')
            ->help('Select the room type');

        // Floor
        $form->number('floor', 'Floor')
            ->rules('nullable|integer|min:-5|max:100')
            ->help('Floor number (negative for basement)')
            ->placeholder('e.g., 1, 2, -1');

        // Wing
        $form->text('wing', 'Wing/Section')
            ->rules('nullable|string|max:50')
            ->help('Wing or section of the building')
            ->placeholder('e.g., North, East, Main');

        // Status
        $form->select('status', 'Status')
            ->options([
                'available' => 'Available',
                'occupied' => 'Occupied',
                'reserved' => 'Reserved',
                'maintenance' => 'Maintenance',
                'dirty' => 'Dirty',
                'out_of_order' => 'Out of Order'
            ])
            ->default('available')
            ->required()
            ->rules('required|in:available,occupied,reserved,maintenance,dirty,out_of_order')
            ->help('Current room status');

        // Housekeeping Status
        $form->select('housekeeping_status', 'Housekeeping Status')
            ->options([
                'clean' => 'Clean',
                'dirty' => 'Dirty',
                'inspected' => 'Inspected',
                'out_of_service' => 'Out of Service'
            ])
            ->default('clean')
            ->required()
            ->rules('required|in:clean,dirty,inspected,out_of_service')
            ->help('Housekeeping status');

        // Last Cleaned At
        $form->datetime('last_cleaned_at', 'Last Cleaned At')
            ->rules('nullable|date')
            ->help('Date and time when room was last cleaned')
            ->default(now());

        // Notes
        $form->textarea('notes', 'Notes')
            ->rules('nullable|string|max:500')
            ->rows(3)
            ->help('Additional notes about the room')
            ->placeholder('Enter any special notes or instructions...');

        // Display timestamps in edit mode
        if ($form->isEditing()) {
            $form->divider();
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        }

        return $form;
    }
}