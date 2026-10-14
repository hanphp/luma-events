<?php
require_once __DIR__.'/shared/api_helpers.php';
jsonHeaders(['GET']);
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/middleware/security.php';
registerRequestLog();
if($_SERVER['REQUEST_METHOD']!=='GET')respond(405,['error'=>'Method not allowed']);
$user=authenticatedUser($conn);$id=validId($_GET['booking_id']??null);
if(!$id)respond(400,['error'=>'A valid booking_id is required']);
$s=$conn->prepare('SELECT b.booking_id,b.user_id,b.quantity,b.total_price,b.booking_status,u.name AS customer_name,e.event_name,e.organiser_id,e.event_date,e.start_time,e.end_time,e.status AS event_status,v.venue_name,v.location,c.checked_in_at FROM bookings b JOIN users u ON u.user_id=b.user_id JOIN events e ON e.event_id=b.event_id JOIN venues v ON v.venue_id=e.venue_id LEFT JOIN ticket_checkins c ON c.booking_id=b.booking_id WHERE b.booking_id=?');$s->bind_param('i',$id);$s->execute();$ticket=$s->get_result()->fetch_assoc();
if(!$ticket)respond(404,['error'=>'Booking not found']);
authorizeBookingRow($user,$ticket);
if($ticket['booking_status']!=='CONFIRMED')respond(409,['error'=>'Ticket is available only after approval']);
if($ticket['event_status']!=='ACTIVE')respond(409,['error'=>'This event is not active']);
$ticket['qr_token']=$ticket['checked_in_at']?null:ticketCode($id);
header('Cache-Control: no-store');
respond(200,['data'=>$ticket]);