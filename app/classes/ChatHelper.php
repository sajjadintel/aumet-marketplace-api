<?php

use Ahc\Jwt\JWT;

class ChatHelper {

    /**
     * Create Chatroom
     *
     * @param GenericModel $dbConnection db connection instance
     * @param int $entitySellerId entity seller ID
     * @param int $entityBuyerId entity buyer ID
     */
    public static function createChatroom($dbConnection, $entitySellerId, $entityBuyerId)
    {
        $dbChatRoom = new GenericModel($dbConnection, "chatroom");
        $dbChatRoom->getWhere("entitySellerId='{$entitySellerId}' AND entityBuyerId = '{$entityBuyerId}'");
        if ($dbChatRoom->dry()) {

            $dbChatRoom->entitySellerId = $entitySellerId;
            $dbChatRoom->entityBuyerId = $entityBuyerId;

            $dbChatRoom->pendingReadSeller = 0;
            $dbChatRoom->pendingReadBuyer = 0;
            $dbChatRoom->updatedAt = date('Y-m-d H:i:s');
            if (!$dbChatRoom->add()) {
                ChatHelper::sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);
            }

        }
        return $dbChatRoom;
    }

    /**
     * Send Message
     *
     * @param GenericModel $dbConnection db connection instance
     * @param \GenericModel $dbChatRoom chatroom Model
     * @param int $userId entity buyer ID
     * @param string $message message
     */
    public static function sendMessage($dbConnection, $dbChatRoom, $userId, $message)
    {
        $dbChatRoom->pendingReadSeller++;
        $dbChatRoom->updatedAt = date('Y-m-d H:i:s');

        $dbChatRoom->archivedBuyerAt = null;
        $dbChatRoom->archivedBuyerAt = null;
        $dbChatRoom->isArchivedBuyer = 0;
        $dbChatRoom->isArchivedSeller = 0;

        if (!$dbChatRoom->update()) {
            ChatHelper::sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);
        }

        $dbChatMessage = new GenericModel($dbConnection, "chatroomDetail");
        $dbChatMessage->chatroomId = $dbChatRoom->id;

        $dbChatMessage->userSenderId = $userId;
        $dbChatMessage->entitySenderId = $dbChatRoom->entityBuyerId;
        $dbChatMessage->entityReceiverId = $dbChatRoom->entitySellerId;
        $dbChatMessage->type = 1;
        $dbChatMessage->content = $message;
        $dbChatMessage->isReadBuyer = 0;
        $dbChatMessage->isReadSeller = 0;
        if (!$dbChatMessage->add()) {
            ChatHelper::sendError(Constants::HTTP_FORBIDDEN, $dbChatMessage->exception, null);
        }
    }

    public static function formatResponse($statusCode = 200, $message = null, $data = null)
    {
        // set the header to make sure cache is forced and treat this as json
        header("Cache-Control: no-transform,public,max-age=300,s-maxage=900");
        header('Content-Type: application/json');
        header("Status: 200 OK");
        http_response_code($statusCode);

        return json_encode(array(
            'statusCode' => $statusCode,
            'message' => $message,
            'data' => $data
        ));
    }

    public static function sendError($statusCode, $message = null, $data = null)
    {
        $response = ChatHelper::formatResponse($statusCode, $message, $data);
//        $this->logResponse(Constants::LOG_TYPE_ERROR, $response);
        echo $response;

        die;
    }


}
