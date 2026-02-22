<?php

namespace Database\Seeders;

use App\Models\MeetingRoom;
use App\Models\User;
use Illuminate\Database\Seeder;

class MeetingRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies
        $companies = User::where('type', 'company')->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
            return;
        }
        
        // Fixed meeting rooms for consistent data
        $meetingRooms = [
            [
                'name' => 'Conference Room A',
                'description' => 'Large conference room with presentation facilities and video conferencing setup',
                'type' => 'Physical',
                'location' => 'Ground Floor, East Wing',
                'capacity' => 20,
                'equipment' => ['Projector', 'Whiteboard', 'Video Conferencing', 'Audio System', 'WiFi'],
                'booking_url' => null,
                'status' => 'active'
            ],
            [
                'name' => 'Meeting Room B',
                'description' => 'Medium-sized meeting room suitable for team meetings and discussions',
                'type' => 'Physical',
                'location' => 'First Floor, North Wing',
                'capacity' => 12,
                'equipment' => ['TV Display', 'Whiteboard', 'Conference Phone', 'WiFi'],
                'booking_url' => null,
                'status' => 'active'
            ],
            [
                'name' => 'Boardroom',
                'description' => 'Executive boardroom for senior management meetings and client presentations',
                'type' => 'Physical',
                'location' => 'Second Floor, Executive Suite',
                'capacity' => 16,
                'equipment' => ['Smart Board', 'Video Conferencing', 'Premium Audio System', 'Climate Control', 'WiFi'],
                'booking_url' => null,
                'status' => 'active'
            ],
            [
                'name' => 'Training Room',
                'description' => 'Spacious training room with flexible seating arrangement for workshops and seminars',
                'type' => 'Physical',
                'location' => 'Ground Floor, West Wing',
                'capacity' => 30,
                'equipment' => ['Projector', 'Sound System', 'Microphones', 'Flipcharts', 'WiFi'],
                'booking_url' => null,
                'status' => 'active'
            ],
            [
                'name' => 'Interview Room 1',
                'description' => 'Small interview room for candidate interviews and one-on-one meetings',
                'type' => 'Physical',
                'location' => 'First Floor, HR Department',
                'capacity' => 4,
                'equipment' => ['Table', 'Chairs', 'WiFi'],
                'booking_url' => null,
                'status' => 'active'
            ],
            [
                'name' => 'Interview Room 2',
                'description' => 'Comfortable interview room with video recording capability for assessments',
                'type' => 'Physical',
                'location' => 'First Floor, HR Department',
                'capacity' => 6,
                'equipment' => ['Video Recording', 'Audio System', 'Whiteboard', 'WiFi'],
                'booking_url' => null,
                'status' => 'active'
            ],
            [
                'name' => 'Zoom Room 1',
                'description' => 'Virtual meeting room for remote team collaboration and client meetings',
                'type' => 'Virtual',
                'location' => 'Online',
                'capacity' => 100,
                'equipment' => ['Screen Sharing', 'Recording', 'Breakout Rooms', 'Chat'],
                'booking_url' => 'https://zoom.us/j/1234567890',
                'status' => 'active'
            ],
            [
                'name' => 'Teams Room 1',
                'description' => 'Microsoft Teams virtual meeting space for internal meetings and presentations',
                'type' => 'Virtual',
                'location' => 'Online',
                'capacity' => 250,
                'equipment' => ['Screen Sharing', 'Recording', 'File Sharing', 'Whiteboard'],
                'booking_url' => 'https://teams.microsoft.com/l/meetup-join/meeting1',
                'status' => 'active'
            ],
            [
                'name' => 'Huddle Space 1',
                'description' => 'Small informal meeting space for quick discussions and brainstorming sessions',
                'type' => 'Physical',
                'location' => 'First Floor, Open Area',
                'capacity' => 6,
                'equipment' => ['Comfortable Seating', 'Whiteboard', 'WiFi'],
                'booking_url' => null,
                'status' => 'active'
            ],
            [
                'name' => 'Webinar Room',
                'description' => 'Virtual webinar room for large-scale presentations and company-wide meetings',
                'type' => 'Virtual',
                'location' => 'Online',
                'capacity' => 500,
                'equipment' => ['HD Video', 'Professional Audio', 'Recording', 'Q&A', 'Polls'],
                'booking_url' => 'https://webinar.company.com/room1',
                'status' => 'active'
            ]
        ];
        
        foreach ($companies as $company) {
            foreach ($meetingRooms as $roomData) {
                // Check if meeting room already exists for this company
                if (MeetingRoom::where('name', $roomData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                try {
                    MeetingRoom::create([
                        'name' => $roomData['name'],
                        'description' => $roomData['description'],
                        'type' => $roomData['type'],
                        'location' => $roomData['location'],
                        'capacity' => $roomData['capacity'],
                        'equipment' => $roomData['equipment'],
                        'booking_url' => $roomData['booking_url'],
                        'status' => $roomData['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create meeting room: ' . $roomData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('MeetingRoom seeder completed successfully!');
    }
}