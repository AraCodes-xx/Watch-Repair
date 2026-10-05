<?php
// Entity Configuration for Unified CRUD System

$entities = [
    'services' => [
        'name' => 'Services',
        'singular' => 'Service',
        'table' => 'services',
        'primary_key' => 'service_id',
        'display_field' => 'service_name',
        'fields' => [
            'service_name' => [
                'label' => 'Service Name',
                'type' => 'text',
                'required' => true,
                'list' => true
            ],
            'description' => [
                'label' => 'Description',
                'type' => 'textarea',
                'required' => false,
                'list' => false
            ],
            'base_price' => [
                'label' => 'Base Price (₱)',
                'type' => 'number',
                'required' => true,
                'list' => true,
                'attributes' => 'step="0.01" min="0"'
            ],
            'duration_hours' => [
                'label' => 'Duration (hours)',
                'type' => 'number',
                'required' => true,
                'list' => true,
                'attributes' => 'min="1"'
            ],
            'is_active' => [
                'label' => 'Active',
                'type' => 'checkbox',
                'required' => false,
                'list' => true,
                'default' => 1
            ]
        ]
    ],
    
    'technicians' => [
        'name' => 'Technicians',
        'singular' => 'Technician',
        'table' => 'technicians',
        'primary_key' => 'technician_id',
        'display_field' => 'full_name',
        'fields' => [
            'full_name' => [
                'label' => 'Full Name',
                'type' => 'text',
                'required' => true,
                'list' => true
            ],
            'email' => [
                'label' => 'Email Address',
                'type' => 'email',
                'required' => true,
                'list' => true,
                'unique' => true
            ],
            'contact_number' => [
                'label' => 'Contact Number',
                'type' => 'text',
                'required' => true,
                'list' => true
            ],
            'specialization' => [
                'label' => 'Specialization',
                'type' => 'text',
                'required' => false,
                'list' => true
            ],
            'is_available' => [
                'label' => 'Available',
                'type' => 'checkbox',
                'required' => false,
                'list' => true,
                'default' => 1
            ]
        ],
        'extra_display' => [
            'rating' => 'Rating',
            'total_jobs' => 'Total Jobs'
        ]
    ],
    
    'users' => [
        'name' => 'Users',
        'singular' => 'User',
        'table' => 'users',
        'primary_key' => 'user_id',
        'display_field' => 'full_name',
        'fields' => [
            'full_name' => [
                'label' => 'Full Name',
                'type' => 'text',
                'required' => true,
                'list' => true
            ],
            'email' => [
                'label' => 'Email Address',
                'type' => 'email',
                'required' => true,
                'list' => true,
                'unique' => true
            ],
            'contact_number' => [
                'label' => 'Contact Number',
                'type' => 'text',
                'required' => true,
                'list' => true
            ],
            'address' => [
                'label' => 'Address',
                'type' => 'textarea',
                'required' => true,
                'list' => false
            ]
        ],
        'extra_display' => [
            'created_at' => 'Registered'
        ]
    ],
    
    'timeslots' => [
        'name' => 'Time Slots',
        'singular' => 'Time Slot',
        'table' => 'timeslots',
        'primary_key' => 'timeslot_id',
        'display_field' => 'start_time',
        'fields' => [
            'start_time' => [
                'label' => 'Start Time',
                'type' => 'time',
                'required' => true,
                'list' => true
            ],
            'end_time' => [
                'label' => 'End Time',
                'type' => 'time',
                'required' => true,
                'list' => true
            ],
            'is_active' => [
                'label' => 'Active',
                'type' => 'checkbox',
                'required' => false,
                'list' => true,
                'default' => 1
            ]
        ]
    ],
    
    'watch_types' => [
        'name' => 'Watch Types',
        'singular' => 'Watch Type',
        'table' => 'watch_types',
        'primary_key' => 'watch_type_id',
        'display_field' => 'type_name',
        'fields' => [
            'type_name' => [
                'label' => 'Watch Type Name',
                'type' => 'text',
                'required' => true,
                'list' => true
            ],
            'description' => [
                'label' => 'Description',
                'type' => 'textarea',
                'required' => false,
                'list' => false
            ],
            'is_active' => [
                'label' => 'Active',
                'type' => 'checkbox',
                'required' => false,
                'list' => true,
                'default' => 1
            ]
        ]
    ]
];

// Get entity configuration
function get_entity_config($entity_name) {
    global $entities;
    return $entities[$entity_name] ?? null;
}

// Get all entity names
function get_all_entities() {
    global $entities;
    return array_keys($entities);
}

// Validate entity exists
function entity_exists($entity_name) {
    global $entities;
    return isset($entities[$entity_name]);
}
?>
