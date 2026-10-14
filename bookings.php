<?php
require_once __DIR__.'/shared/api_helpers.php';
jsonHeaders(['GET','POST','PUT','DELETE']);
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/middleware/security.php';
$method=$_SERVER['REQUEST_METHOD'];
$id=isset($_GET['id'])?validId($_GET['id']):null;
if(isset($_GET['id']) && $id===null)respond(400,['error'=>'Invalid booking ID']);
securityGate($conn,'bookings',$method,$id);
$user=$GLOBALS['security_user'];
if($method==='GET') {
    $sql='SELECT b.booking_id,b.user_id,u.name AS customer_name,b.event_id,e.event_name,e.organiser_id,e.event_date,b.quantity,b.total_price,b.booking_status,b.booking_date,c.checked_in_at FROM bookings b JOIN users u ON u.user_id=b.user_id JOIN events e ON e.event_id=b.event_id LEFT JOIN ticket_checkins c ON c.booking_id=b.booking_id';
    $clauses=[];$params=[];
    if($id!==null){$clauses[]='b.booking_id=?';$params[]=$id;}
    if($user['role']==='CUSTOMER'){$clauses[]='b.user_id=?';$params[]=(int)$user['user_id'];}
    elseif($user['role']==='ORGANISER'){$clauses[]='e.organiser_id=?';$params[]=(int)$user['user_id'];}
    if($clauses)$sql.=' WHERE '.implode(' AND ',$clauses);
    $sql.=' ORDER BY b.booking_id DESC';$s=$conn->prepare($sql);
    if($params)$s->bind_param(str_repeat('i',count($params)),...$params);
    $s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);
    if($id!==null){if(!$rows)respond(404,['error'=>'Booking not found']);respond(200,['data'=>$rows[0]]);}
    respond(200,['data'=>$rows]);
}
if(!in_array($method,['POST','PUT','DELETE'],true))respond(405,['error'=>'Method not allowed']);
if(!in_array($_SERVER['REMOTE_ADDR']??'', ['127.0.0.1','::1'],true))respond(403,['error'=>'Booking changes are restricted to localhost during development']);
if($method==='POST') {
    if($id!==null)respond(400,['error'=>'POST must target /bookings']);
    $d=readJson();$eventId=validId($d['event_id']??null);$quantity=$d['quantity']??null;
    if($user['role']==='CUSTOMER'){
        $userId=(int)$user['user_id'];
        if(isset($d['user_id']) && validId($d['user_id'])!==$userId)respond(403,['error'=>'You can only book for your own account']);
    }else{$userId=validId($d['user_id']??null);}
    if(!$userId||!$eventId||!is_int($quantity)||$quantity<1)respond(422,['error'=>'Valid customer, event_id and quantity are required']);
    $s=$conn->prepare('SELECT role FROM users WHERE user_id=?');$s->bind_param('i',$userId);$s->execute();$customer=$s->get_result()->fetch_assoc();
    if(!$customer||$customer['role']!=='CUSTOMER')respond(422,['error'=>'Booking owner must be a CUSTOMER']);
    try{
        $conn->begin_transaction();
        $s=$conn->prepare('SELECT ticket_price,ticket_quantity,status,event_date FROM events WHERE event_id=? FOR UPDATE');$s->bind_param('i',$eventId);$s->execute();$event=$s->get_result()->fetch_assoc();
        if(!$event){$conn->rollback();respond(404,['error'=>'Event not found']);}
        if($event['status']!=='ACTIVE'||$event['event_date']<date('Y-m-d')){$conn->rollback();respond(409,['error'=>'Event is not available for booking']);}
        $s=$conn->prepare("SELECT COALESCE(SUM(quantity),0) AS reserved FROM bookings WHERE event_id=? AND booking_status IN ('PENDING','CONFIRMED')");$s->bind_param('i',$eventId);$s->execute();$reserved=(int)$s->get_result()->fetch_assoc()['reserved'];$available=(int)$event['ticket_quantity']-$reserved;
        if($quantity>$available){$conn->rollback();respond(409,['error'=>'Not enough tickets available','available_tickets'=>max(0,$available)]);}
        $total=round((float)$event['ticket_price']*$quantity,2);$status='PENDING';
        $s=$conn->prepare('INSERT INTO bookings(user_id,event_id,quantity,total_price,booking_status) VALUES(?,?,?,?,?)');$s->bind_param('iiids',$userId,$eventId,$quantity,$total,$status);$s->execute();$newId=$conn->insert_id;$conn->commit();
    }catch(Throwable $e){$conn->rollback();error_log('[event-api] Booking creation failed');respond(500,['error'=>'Booking could not be created']);}
    respond(201,['message'=>'Booking created successfully','data'=>['booking_id'=>$newId,'user_id'=>$userId,'event_id'=>$eventId,'quantity'=>$quantity,'total_price'=>$total,'booking_status'=>'PENDING']]);
}
if($id===null)respond(400,['error'=>'Booking ID required']);
if($method==='PUT'){
    $d=readJson();$newStatus=$d['booking_status']??null;
    if(!is_string($newStatus)||!in_array($newStatus,['CONFIRMED','CANCELLED'],true))respond(422,['error'=>'booking_status must be CONFIRMED or CANCELLED']);
}else{$newStatus='CANCELLED';}
try{
    $conn->begin_transaction();
    $s=$conn->prepare('SELECT b.user_id,b.booking_status,e.organiser_id,e.event_date,e.status AS event_status FROM bookings b JOIN events e ON e.event_id=b.event_id WHERE b.booking_id=? FOR UPDATE');$s->bind_param('i',$id);$s->execute();$booking=$s->get_result()->fetch_assoc();
    if(!$booking){$conn->rollback();respond(404,['error'=>'Booking not found']);}
    authorizeBookingRow($user,$booking);
    $s=$conn->prepare('SELECT booking_id FROM ticket_checkins WHERE booking_id=?');$s->bind_param('i',$id);$s->execute();
    if($s->get_result()->fetch_assoc()){$conn->rollback();respond(409,['error'=>'A checked-in ticket cannot be changed']);}
    if($booking['booking_status']==='CANCELLED'||$booking['booking_status']===$newStatus){$conn->rollback();respond(409,['error'=>'Booking is already '.$booking['booking_status']]);}
    if($booking['event_date']<date('Y-m-d')){$conn->rollback();respond(409,['error'=>'Past-event bookings cannot be changed']);}
    if($newStatus==='CONFIRMED'&&($booking['booking_status']!=='PENDING'||$booking['event_status']!=='ACTIVE')){$conn->rollback();respond(409,['error'=>'Only pending bookings for active events can be confirmed']);}
    $s=$conn->prepare('UPDATE bookings SET booking_status=? WHERE booking_id=?');$s->bind_param('si',$newStatus,$id);$s->execute();$conn->commit();
}catch(Throwable $e){$conn->rollback();error_log('[event-api] Booking status update failed');respond(500,['error'=>'Booking status could not be updated']);}
respond(200,['message'=>'Booking status updated successfully','data'=>['booking_id'=>$id,'booking_status'=>$newStatus]]);