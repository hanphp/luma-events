<?php
require_once __DIR__.'/shared/api_helpers.php';
jsonHeaders(['POST']);
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/middleware/security.php';
registerRequestLog();
if($_SERVER['REQUEST_METHOD']!=='POST')respond(405,['error'=>'Method not allowed']);
$user=authenticatedUser($conn);requireRoles($user,['ADMIN','ORGANISER']);
$d=readJson();$code=$d['qr_token']??null;
if(!is_string($code)||!preg_match('/^LUMA1\.([1-9][0-9]*)\.[a-f0-9]{64}$/D',$code,$m))respond(422,['error'=>'Scan or paste a valid Luma QR code']);
$id=validId($m[1]);if(!$id||!hash_equals(ticketCode($id),$code))respond(403,['error'=>'Invalid ticket signature']);
try{
    $conn->begin_transaction();
    $s=$conn->prepare('SELECT b.booking_id,b.user_id,b.quantity,b.booking_status,e.organiser_id,e.event_name,e.status AS event_status,e.event_date FROM bookings b JOIN events e ON e.event_id=b.event_id WHERE b.booking_id=? FOR UPDATE');$s->bind_param('i',$id);$s->execute();$booking=$s->get_result()->fetch_assoc();
    if(!$booking){$conn->rollback();respond(404,['error'=>'Booking not found']);}
    authorizeBookingRow($user,$booking);
    if($booking['booking_status']!=='CONFIRMED'||$booking['event_status']!=='ACTIVE'||$booking['event_date']<date('Y-m-d')){$conn->rollback();respond(409,['error'=>'Only confirmed tickets for active, non-past events can be checked in']);}
    $s=$conn->prepare('SELECT checked_in_at FROM ticket_checkins WHERE booking_id=?');$s->bind_param('i',$id);$s->execute();if($s->get_result()->fetch_assoc()){$conn->rollback();respond(409,['error'=>'Ticket has already been checked in']);}
    $staff=(int)$user['user_id'];$s=$conn->prepare('INSERT INTO ticket_checkins(booking_id,checked_in_by) VALUES(?,?)');$s->bind_param('ii',$id,$staff);$s->execute();$conn->commit();
}catch(Throwable $e){$conn->rollback();error_log('[event-api] Ticket check-in failed');respond(500,['error'=>'Unable to check in ticket']);}
respond(200,['message'=>'Check-in accepted','data'=>['booking_id'=>$id,'quantity'=>(int)$booking['quantity'],'event_name'=>$booking['event_name'],'checked_in'=>true]]);