<?php

class MessageController extends MainController {

    public function getChatRoomList()
    {
        $limit = 10;
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];
        $order['limit'] = $limit;
        if (!is_numeric($limit))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Limit')), null);

        $offset = 0;
        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        $order['offset'] = $offset;
        if (!is_numeric($offset))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Offset')), null);

        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $filter = "buyerEntityId IN ($arrEntityId)";
        $filter .= " AND archivedAt IS NULL";

        $dbChatroom = new GenericModel($this->db, "vwChatroom");
        $dataCount = $dbChatroom->count($filter);
        $dbChatroom->reset();

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;

        $response['dataFilter'] = $dataFilter;
        $response['data'] = array_map(array($dbChatroom, 'cast'), $dbChatroom->find($filter, $order));

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_order')), $response);
    }

    public function getMessageList()
    {
        if (!$this->f3->get('PARAMS.id') || !is_numeric($this->f3->get('PARAMS.id')))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_chatRoomId')), null);

        $chatRoomId = $this->f3->get('PARAMS.id');

        $dbChatRoom = new GenericModel($this->db, "chatroom");
        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $dbChatRoom->getWhere("id = '$chatRoomId' AND buyerEntityId IN ($arrEntityId)");

        if ($dbChatRoom->dry())
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_chatRoom')), null);


        $dbChatMessage = new GenericModel($this->db, "chatroomDetail");
        $chats = $dbChatMessage->findWhere("chatroomId = '$chatRoomId' ");


        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_message')), $chats);
    }

    public function postSetMessagesUnread()
    {
        if (!$this->requestData->chatroomId || !is_numeric($this->requestData->chatroomId))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_chatRoomId')), null);
        if (!$this->requestData->messageIds || !preg_match('/^[0-9,]+$/', $this->requestData->messageIds))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_messageIds')), null);

        $chatRoomId = $this->requestData->chatroomId;
        $messageIds = $this->requestData->messageIds;

        $dbChatRoom = new GenericModel($this->db, "chatroom");
        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $dbChatRoom->getWhere("id = '$chatRoomId' AND buyerEntityId IN ($arrEntityId)");

        if ($dbChatRoom->dry())
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_chatRoom')), null);

        $updatedMessages = $this->db->exec("UPDATE chatroomDetail SET isRead = 0 WHERE isRead=1 AND id IN ({$messageIds}) AND chatroomId={$chatRoomId} AND receiverEntityId IN ($arrEntityId)");

        $dbChatRoom->buyerPendingRead += $updatedMessages;

        if (!$dbChatRoom->update())
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_updated', $this->f3->get('RESPONSE.entity_message')), null);
    }

    public function postSetMessagesRead()
    {
        if (!$this->requestData->chatroomId || !is_numeric($this->requestData->chatroomId))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_chatRoomId')), null);
        if (!$this->requestData->messageIds || !preg_match('/^[0-9,]+$/', $this->requestData->messageIds))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_messageIds')), null);

        $chatRoomId = $this->requestData->chatroomId;
        $messageIds = $this->requestData->messageIds;

        $dbChatRoom = new GenericModel($this->db, "chatroom");
        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $dbChatRoom->getWhere("id = '$chatRoomId' AND buyerEntityId IN ($arrEntityId)");

        if ($dbChatRoom->dry())
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_chatRoom')), null);

        $updatedMessages = $this->db->exec("UPDATE chatroomDetail SET isRead = 1 WHERE isRead=0 AND id IN ({$messageIds}) AND chatroomId={$chatRoomId} AND receiverEntityId IN ($arrEntityId)");

        $dbChatRoom->buyerPendingRead -= $updatedMessages;

        if (!$dbChatRoom->update())
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_updated', $this->f3->get('RESPONSE.entity_message')), null);
    }

    public function postSetChatRoomArchive()
    {
        if (!$this->requestData->chatroomId || !is_numeric($this->requestData->chatroomId))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_chatRoomId')), null);

        $chatRoomId = $this->requestData->chatroomId;

        $dbChatRoom = new GenericModel($this->db, "chatroom");
        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $dbChatRoom->getWhere("id = '$chatRoomId' AND buyerEntityId IN ($arrEntityId)");

        if ($dbChatRoom->dry())
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_chatRoom')), null);

        $dbChatRoom->archivedAt = date('Y-m-d H:i:s');

        if (!$dbChatRoom->update())
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_updated', $this->f3->get('RESPONSE.entity_chatRoom')), null);
    }

    public function postNewMessage()
    {
        if (!$this->requestData->chatroomId || !is_numeric($this->requestData->chatroomId))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_chatRoomId')), null);
        if (!$this->requestData->message)
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_message')), null);

        $chatRoomId = $this->requestData->chatroomId;
        $message = $this->requestData->message;

        $dbChatRoom = new GenericModel($this->db, "chatroom");
        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $dbChatRoom->getWhere("id = '$chatRoomId' AND buyerEntityId IN ($arrEntityId)");

        if ($dbChatRoom->dry())
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_chatRoom')), null);

        $dbChatRoom->sellerPendingRead++;
        $dbChatRoom->updatedAt = date('Y-m-d H:i:s');
        $dbChatRoom->archivedAt = null;

        if (!$dbChatRoom->update())
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);

        $dbChatMessage = new GenericModel($this->db, "chatroomDetail");
        $dbChatMessage->chatroomId = $dbChatRoom->id;
        $dbChatMessage->senderUserId = $this->objUser->id;
        $dbChatMessage->senderEntityId = $dbChatRoom->buyerEntityId;
        $dbChatMessage->receiverEntityId = $dbChatRoom->sellerEntityId;
        $dbChatMessage->type = 1;
        $dbChatMessage->content = $message;
        $dbChatMessage->isRead = 0;
        if (!$dbChatMessage->add())
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatMessage->exception, null);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_message')), null);
    }

    public function postNewChatRoom()
    {
        if (!$this->requestData->chatroomId || !is_numeric($this->requestData->chatroomId))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_sellerId')), null);

        $chatRoomId = $this->requestData->chatroomId;

        $arrEntityId = Helper::idListFromArray($this->objEntityList);

        $dbChatRoom = new GenericModel($this->db, "chatroom");
        $dbChatRoom->sellerEntityId = $chatRoomId;
        $dbChatRoom->buyerEntityId = $arrEntityId[0];
        $dbChatRoom->sellerPendingRead = 0;
        $dbChatRoom->buyerPendingRead = 0;
        $dbChatRoom->updatedAt = date('Y-m-d H:i:s');
        if (!$dbChatRoom->add())
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_chatRoom')), null);
    }

}
