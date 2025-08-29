<?php

class EvolutionAPI {
    private $baseUrl;
    private $globalApikey;
    private $instance;
    private $apikey;
    
    public function __construct($baseUrl, $globalApikey, $instance, $apikey = null) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->globalApikey = $globalApikey;
        $this->instance = $instance;
        $this->apikey = $apikey;
    }
    
    /**
     * Realiza una solicitud HTTP
     */
    private function request($method, $endpoint, $data = null, $headers = []) {
        $url = $this->baseUrl . $endpoint;
        
        $defaultHeaders = [
            'apikey: ' . ($this->apikey ? $this->apikey : $this->globalApikey),
            'Content-Type: application/json'
        ];
        
        $headers = array_merge($defaultHeaders, $headers);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }
    
    // ==================== INSTANCE METHODS ====================
    
    public function createInstance($instanceData) {
        return $this->request('POST', '/instance/create', $instanceData);
    }
    
    public function fetchInstances($instanceName = null, $instanceId = null) {
        $queryParams = [];
        if ($instanceName) $queryParams['instanceName'] = $instanceName;
        if ($instanceId) $queryParams['instanceId'] = $instanceId;
        
        $endpoint = '/instance/fetchInstances';
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }
        
        return $this->request('GET', $endpoint);
    }
    
    public function instanceConnect($number = null) {
        $endpoint = '/instance/connect/' . $this->instance;
        if ($number) {
            $endpoint .= '?number=' . urlencode($number);
        }
        
        return $this->request('GET', $endpoint);
    }
    
    public function restartInstance() {
        return $this->request('POST', '/instance/restart/' . $this->instance);
    }
    
    public function setPresence($presence) {
        $data = ['presence' => $presence];
        return $this->request('POST', '/instance/setPresence/' . $this->instance, $data);
    }
    
    public function connectionState() {
        return $this->request('GET', '/instance/connectionState/' . $this->instance);
    }
    
    public function logoutInstance() {
        return $this->request('DELETE', '/instance/logout/' . $this->instance);
    }
    
    public function deleteInstance() {
        return $this->request('DELETE', '/instance/delete/' . $this->instance);
    }
    
    // ==================== PROXY METHODS ====================
    
    public function setProxy($proxyData) {
        return $this->request('POST', '/proxy/set/' . $this->instance, $proxyData);
    }
    
    public function findProxy() {
        return $this->request('GET', '/proxy/find/' . $this->instance);
    }
    
    // ==================== SETTINGS METHODS ====================
    
    public function setSettings($settingsData) {
        return $this->request('POST', '/settings/set/' . $this->instance, $settingsData);
    }
    
    public function findSettings() {
        return $this->request('GET', '/settings/find/' . $this->instance);
    }
    
    // ==================== MESSAGE METHODS ====================
    
    public function sendText($number, $text, $options = []) {
        $data = array_merge([
            'number' => $number,
            'text' => $text
        ], $options);
        
        return $this->request('POST', '/message/sendText/' . $this->instance, $data);
    }
    
    public function sendMedia($mediaData) {
        return $this->request('POST', '/message/sendMedia/' . $this->instance, $mediaData);
    }
    
    public function sendPTV($ptvData) {
        return $this->request('POST', '/message/sendPtv/' . $this->instance, $ptvData);
    }
    
    public function sendWhatsAppAudio($audioData) {
        return $this->request('POST', '/message/sendWhatsAppAudio/' . $this->instance, $audioData);
    }
    
    public function sendStatus($statusData) {
        return $this->request('POST', '/message/sendStatus/' . $this->instance, $statusData);
    }
    
    public function sendSticker($stickerData) {
        return $this->request('POST', '/message/sendSticker/' . $this->instance, $stickerData);
    }
    
    public function sendLocation($locationData) {
        return $this->request('POST', '/message/sendLocation/' . $this->instance, $locationData);
    }
    
    public function sendContact($contactData) {
        return $this->request('POST', '/message/sendContact/' . $this->instance, $contactData);
    }
    
    public function sendReaction($reactionData) {
        return $this->request('POST', '/message/sendReaction/' . $this->instance, $reactionData);
    }
    
    public function sendPoll($pollData) {
        return $this->request('POST', '/message/sendPoll/' . $this->instance, $pollData);
    }
    
    public function sendList($listData) {
        return $this->request('POST', '/message/sendList/' . $this->instance, $listData);
    }
    
    public function sendButtons($buttonsData) {
        return $this->request('POST', '/message/sendButtons/' . $this->instance, $buttonsData);
    }
    
    // ==================== CALL METHODS ====================
    
    public function fakeCall($callData) {
        return $this->request('POST', '/call/offer/' . $this->instance, $callData);
    }
    
    // ==================== CHAT METHODS ====================
    
    public function checkWhatsAppNumbers($numbers) {
        $data = ['numbers' => $numbers];
        return $this->request('POST', '/chat/whatsappNumbers/' . $this->instance, $data);
    }
    
    public function readMessages($readMessagesData) {
        return $this->request('POST', '/chat/markMessageAsRead/' . $this->instance, $readMessagesData);
    }
    
    public function archiveChat($archiveData) {
        return $this->request('POST', '/chat/archiveChat/' . $this->instance, $archiveData);
    }
    
    public function markChatUnread($unreadData) {
        return $this->request('POST', '/chat/markChatUnread/' . $this->instance, $unreadData);
    }
    
    public function deleteMessage($deleteData) {
        return $this->request('DELETE', '/chat/deleteMessageForEveryone/' . $this->instance, $deleteData);
    }
    
    public function fetchProfilePicture($number) {
        $data = ['number' => $number];
        return $this->request('POST', '/chat/fetchProfilePictureUrl/' . $this->instance, $data);
    }
    
    public function getBase64FromMediaMessage($mediaData) {
        return $this->request('POST', '/chat/getBase64FromMediaMessage/' . $this->instance, $mediaData);
    }
    
    public function updateMessage($updateData) {
        return $this->request('POST', '/chat/updateMessage/' . $this->instance, $updateData);
    }
    
    public function sendPresence($presenceData) {
        return $this->request('POST', '/chat/sendPresence/' . $this->instance, $presenceData);
    }
    
    public function updateBlockStatus($blockData) {
        return $this->request('POST', '/message/updateBlockStatus/' . $this->instance, $blockData);
    }
    
    public function findContacts($where = []) {
        $data = ['where' => $where];
        return $this->request('POST', '/chat/findContacts/' . $this->instance, $data);
    }
    
    public function findMessages($where = [], $page = null, $offset = null) {
        $data = ['where' => $where];
        if ($page) $data['page'] = $page;
        if ($offset) $data['offset'] = $offset;
        
        return $this->request('POST', '/chat/findMessages/' . $this->instance, $data);
    }
    
    public function findStatusMessage($where = [], $page = null, $offset = null) {
        $data = ['where' => $where];
        if ($page) $data['page'] = $page;
        if ($offset) $data['offset'] = $offset;
        
        return $this->request('POST', '/chat/findStatusMessage/' . $this->instance, $data);
    }
    
    public function findChats() {
        return $this->request('POST', '/chat/findChats/' . $this->instance);
    }
    
    // ==================== LABEL METHODS ====================
    
    public function findLabels() {
        return $this->request('GET', '/label/findLabels/' . $this->instance);
    }
    
    public function handleLabel($labelData) {
        return $this->request('POST', '/label/handleLabel/' . $this->instance, $labelData);
    }
    
    // ==================== PROFILE SETTINGS METHODS ====================
    
    public function fetchBusinessProfile($number) {
        $data = ['number' => $number];
        return $this->request('POST', '/chat/fetchBusinessProfile/' . $this->instance, $data);
    }
    
    public function fetchProfile($number) {
        $data = ['number' => $number];
        return $this->request('POST', '/chat/fetchProfile/' . $this->instance, $data);
    }
    
    public function updateProfileName($name) {
        $data = ['name' => $name];
        return $this->request('POST', '/chat/updateProfileName/' . $this->instance, $data);
    }
    
    public function updateProfileStatus($status) {
        $data = ['status' => $status];
        return $this->request('POST', '/chat/updateProfileStatus/' . $this->instance, $data);
    }
    
    public function updateProfilePicture($picture) {
        $data = ['picture' => $picture];
        return $this->request('POST', '/chat/updateProfilePicture/' . $this->instance, $data);
    }
    
    public function removeProfilePicture() {
        return $this->request('DELETE', '/chat/removeProfilePicture/' . $this->instance);
    }
    
    public function fetchPrivacySettings() {
        return $this->request('GET', '/chat/fetchPrivacySettings/' . $this->instance);
    }
    
    public function updatePrivacySettings($privacyData) {
        return $this->request('POST', '/chat/updatePrivacySettings/' . $this->instance, $privacyData);
    }
    
    // ==================== GROUP METHODS ====================
    
    public function createGroup($groupData) {
        return $this->request('POST', '/group/create/' . $this->instance, $groupData);
    }
    
    public function updateGroupPicture($groupJid, $image) {
        $data = ['image' => $image];
        $endpoint = '/group/updateGroupPicture/' . $this->instance . '?groupJid=' . urlencode($groupJid);
        return $this->request('POST', $endpoint, $data);
    }
    
    public function updateGroupSubject($groupJid, $subject) {
        $data = ['subject' => $subject];
        $endpoint = '/group/updateGroupSubject/' . $this->instance . '?groupJid=' . urlencode($groupJid);
        return $this->request('POST', $endpoint, $data);
    }
    
    public function updateGroupDescription($groupJid, $description) {
        $data = ['description' => $description];
        $endpoint = '/group/updateGroupDescription/' . $this->instance . '?groupJid=' . urlencode($groupJid);
        return $this->request('POST', $endpoint, $data);
    }
    
    public function fetchInviteCode($groupJid) {
        $endpoint = '/group/inviteCode/' . $this->instance . '?groupJid=' . urlencode($groupJid);
        return $this->request('GET', $endpoint);
    }
    
    public function revokeInviteCode($groupJid) {
        $endpoint = '/group/revokeInviteCode/' . $this->instance . '?groupJid=' . urlencode($groupJid);
        return $this->request('POST', $endpoint);
    }
    
    public function sendInviteUrl($inviteData) {
        return $this->request('POST', '/group/sendInvite/' . $this->instance, $inviteData);
    }
    
    public function findGroupByInviteCode($inviteCode) {
        $endpoint = '/group/inviteInfo/' . $this->instance . '?inviteCode=' . urlencode($inviteCode);
        return $this->request('GET', $endpoint);
    }
    
    public function findGroupByJid($groupJid) {
        $endpoint = '/group/findGroupInfos/' . $this->instance . '?groupJid=' . urlencode($groupJid);
        return $this->request('GET', $endpoint);
    }
    
    public function fetchAllGroups($getParticipants = false) {
        $endpoint = '/group/fetchAllGroups/' . $this->instance . '?getParticipants=' . ($getParticipants ? 'true' : 'false');
        return $this->request('GET', $endpoint);
    }
    
    public function findParticipants($groupJid) {
        $endpoint = '/group/participants/' . $this->instance . '?groupJid=' . urlencode($groupJid);
        return $this->request('GET', $endpoint);
    }
    
    public function updateParticipant($groupJid, $participantData) {
        $endpoint = '/group/updateParticipant/' . $this->instance . '?groupJid=' . urlencode($groupJid);
        return $this->request('POST', $endpoint, $participantData);
    }
    
    public function updateSetting($groupJid, $settingData) {
        $endpoint = '/group/updateSetting/' . $this->instance . '?groupJid=' . urlencode($groupJid);
        return $this->request('POST', $endpoint, $settingData);
    }
    
    public function toggleEphemeral($groupJid, $expirationData) {
        $endpoint = '/group/toggleEphemeral/' . $this->instance . '?groupJid=' . urlencode($groupJid);
        return $this->request('POST', $endpoint, $expirationData);
    }
    
    public function leaveGroup($groupJid) {
        $endpoint = '/group/leaveGroup/' . $this->instance . '?groupJid=' . urlencode($groupJid);
        return $this->request('DELETE', $endpoint);
    }
    
    // ==================== INTEGRATION METHODS ====================
    // Nota: Solo incluyo algunos métodos de integración como ejemplo
    
    public function setWebsocket($websocketData) {
        return $this->request('POST', '/websocket/set/' . $this->instance, $websocketData);
    }
    
    public function findWebsocket() {
        return $this->request('GET', '/websocket/find/' . $this->instance);
    }
    
    public function setChatwoot($chatwootData) {
        return $this->request('POST', '/chatwoot/set/' . $this->instance, $chatwootData);
    }
    
    public function findChatwoot() {
        return $this->request('GET', '/chatwoot/find/' . $this->instance);
    }
    
    // ==================== GET INFORMATION METHOD ====================
    
    public function getInformation() {
        return $this->request('GET', '');
    }
}

// Ejemplo de uso:
/*
$evolution = new EvolutionAPI(
    'https://sub.domain.com', 
    'your_global_api_key', 
    'your_instance_name',
    'your_instance_api_key' // opcional
);

// Crear una instancia
$instanceData = [
    'instanceName' => 'my_instance',
    'qrcode' => true,
    'integration' => 'WHATSAPP-BAILEYS'
];
$result = $evolution->createInstance($instanceData);

// Enviar un mensaje de texto
$messageData = [
    'number' => '559999999999',
    'text' => 'Hola, este es un mensaje de prueba'
];
$result = $evolution->sendText($messageData);

// Verificar el estado de conexión
$result = $evolution->connectionState();
*/
?>