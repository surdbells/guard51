<?php
declare(strict_types=1);
namespace Guard51\Tests\Entity;

use Guard51\Entity\ChatConversation;
use Guard51\Entity\ChatMessage;
use Guard51\Entity\ChatParticipant;
use Guard51\Entity\ChatParticipantRole;
use Guard51\Entity\ClientUser;
use Guard51\Entity\ConversationType;
use Guard51\Entity\DevicePlatform;
use Guard51\Entity\DeviceToken;
use Guard51\Entity\MessageType;
use Guard51\Entity\Notification;
use Guard51\Entity\NotificationChannel;
use Guard51\Entity\NotificationType;
use PHPUnit\Framework\TestCase;

class ClientChatNotificationTest extends TestCase
{
    // ── ClientUser ───────────────────────────────────

    public function testClientUserCreation(): void
    {
        $cu = new ClientUser();
        $cu->setTenantId('t-1')->setClientId('c-1')->setUserId('u-1');
        $arr = $cu->toArray();
        $this->assertEquals('c-1', $arr['client_id']);
        $this->assertTrue($arr['can_view_reports']);
        $this->assertTrue($arr['can_view_tracking']);
        $this->assertTrue($arr['can_view_invoices']);
        $this->assertTrue($arr['can_view_incidents']);
        $this->assertTrue($arr['can_message']);
    }

    public function testClientUserPermissions(): void
    {
        $cu = new ClientUser();
        $cu->setTenantId('t-1')->setClientId('c-1')->setUserId('u-1')
            ->setCanViewTracking(false)->setCanMessage(false);
        $arr = $cu->toArray();
        $this->assertFalse($arr['can_view_tracking']);
        $this->assertFalse($arr['can_message']);
        $this->assertTrue($arr['can_view_reports']); // default
    }

    // ── ChatConversation ─────────────────────────────

    public function testConversationCreation(): void
    {
        $conv = new ChatConversation();
        $conv->setTenantId('t-1')->setType(ConversationType::DIRECT)->setCreatedBy('u-1');
        $this->assertEquals(ConversationType::DIRECT, $conv->getType());
        $this->assertNull($conv->getName());
    }

    public function testSiteChannel(): void
    {
        $conv = new ChatConversation();
        $conv->setTenantId('t-1')->setType(ConversationType::SITE_CHANNEL)->setName('#Lekki Phase 1')
            ->setSiteId('s-1')->setCreatedBy('system');
        $arr = $conv->toArray();
        $this->assertEquals('site_channel', $arr['type']);
        $this->assertEquals('#Lekki Phase 1', $arr['name']);
        $this->assertEquals('s-1', $arr['site_id']);
    }

    public function testConversationLastMessage(): void
    {
        $conv = new ChatConversation();
        $conv->setTenantId('t-1')->setType(ConversationType::GROUP)->setCreatedBy('u-1');
        $this->assertNull($conv->toArray()['last_message_at']);
        $conv->updateLastMessageAt();
        $this->assertNotNull($conv->toArray()['last_message_at']);
    }

    // ── ChatParticipant ──────────────────────────────

    public function testParticipant(): void
    {
        $p = new ChatParticipant();
        $p->setConversationId('conv-1')->setUserId('u-1')->setRole(ChatParticipantRole::ADMIN);
        $arr = $p->toArray();
        $this->assertEquals('admin', $arr['role']);
        $this->assertNull($arr['left_at']);
    }

    public function testParticipantReadAndLeave(): void
    {
        $p = new ChatParticipant();
        $p->setConversationId('conv-1')->setUserId('u-1');
        $p->markRead();
        $this->assertNotNull($p->toArray()['last_read_at']);
        $p->leave();
        $this->assertNotNull($p->toArray()['left_at']);
    }

    // ── ChatMessage ──────────────────────────────────

    public function testTextMessage(): void
    {
        $msg = new ChatMessage();
        $msg->setConversationId('conv-1')->setSenderId('u-1')->setContent('Hello team!');
        $arr = $msg->toArray();
        $this->assertEquals('text', $arr['message_type']);
        $this->assertEquals('Hello team!', $arr['content']);
        $this->assertFalse($arr['is_deleted']);
    }

    public function testMediaMessage(): void
    {
        $msg = new ChatMessage();
        $msg->setConversationId('conv-1')->setSenderId('u-1')->setContent('Photo from patrol')
            ->setMessageType(MessageType::IMAGE)->setMediaUrl('/uploads/chat/img001.jpg');
        $arr = $msg->toArray();
        $this->assertEquals('image', $arr['message_type']);
        $this->assertNotNull($arr['media_url']);
    }

    public function testLocationMessage(): void
    {
        $msg = new ChatMessage();
        $msg->setConversationId('conv-1')->setSenderId('u-1')->setContent('My location')
            ->setMessageType(MessageType::LOCATION)->setLatitude(6.4541)->setLongitude(3.3947);
        $arr = $msg->toArray();
        $this->assertEquals('location', $arr['message_type']);
        $this->assertEquals(6.4541, $arr['lat']);
    }

    public function testSoftDelete(): void
    {
        $msg = new ChatMessage();
        $msg->setConversationId('conv-1')->setSenderId('u-1')->setContent('Secret message')
            ->setMediaUrl('/secret.jpg');
        $msg->softDelete();
        $arr = $msg->toArray();
        $this->assertTrue($arr['is_deleted']);
        $this->assertEquals('[Message deleted]', $arr['content']);
        $this->assertNull($arr['media_url']);
    }

    // ── Notification ─────────────────────────────────

    public function testNotificationCreation(): void
    {
        $n = new Notification();
        $n->setTenantId('t-1')->setUserId('u-1')->setType(NotificationType::INCIDENT)
            ->setTitle('New Incident')->setBody('Theft reported at Lekki Phase 1')
            ->setData(['incident_id' => 'inc-1']);
        $arr = $n->toArray();
        $this->assertEquals('incident', $arr['type']);
        $this->assertEquals('Incident', $arr['type_label']);
        $this->assertEquals('in_app', $arr['channel']);
        $this->assertFalse($arr['is_read']);
    }

    public function testNotificationMarkRead(): void
    {
        $n = new Notification();
        $n->setTenantId('t-1')->setUserId('u-1')->setType(NotificationType::SHIFT_ASSIGNED)
            ->setTitle('Shift Assigned')->setBody('You have been assigned to Day Shift');
        $this->assertFalse($n->isRead());
        $n->markRead();
        $this->assertTrue($n->isRead());
        $this->assertNotNull($n->toArray()['read_at']);
    }

    public function testNotificationChannels(): void
    {
        $this->assertEquals('PUSH', NotificationChannel::PUSH->label());
        $this->assertEquals('SMS', NotificationChannel::SMS->label());
        $this->assertEquals('In-App', NotificationChannel::IN_APP->label());
    }

    public function testNotificationTypes(): void
    {
        $this->assertEquals('Shift assigned', NotificationType::SHIFT_ASSIGNED->label());
        $this->assertEquals('Panic', NotificationType::PANIC->label());
        $this->assertEquals('System', NotificationType::SYSTEM->label());
    }

    // ── DeviceToken ──────────────────────────────────

    public function testDeviceToken(): void
    {
        $dt = new DeviceToken();
        $dt->setUserId('u-1')->setToken('fcm_token_abc123xyz789...')->setPlatform(DevicePlatform::ANDROID);
        $arr = $dt->toArray();
        $this->assertEquals('android', $arr['platform']);
        $this->assertEquals('Android', $arr['platform_label']);
        $this->assertTrue($arr['is_active']);
        $this->assertStringEndsWith('...', $arr['token']); // truncated for security
    }

    public function testDeviceDeactivation(): void
    {
        $dt = new DeviceToken();
        $dt->setUserId('u-1')->setToken('test')->setPlatform(DevicePlatform::IOS);
        $dt->deactivate();
        $this->assertFalse($dt->toArray()['is_active']);
    }
}
