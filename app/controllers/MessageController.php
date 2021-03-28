<?php

class MessageController extends MainController
{

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
        $filter = "entityBuyerId IN ($arrEntityId)";
        $filter .= " AND isArchivedBuyer=0";

        $dbChatroom = new GenericModel($this->db, "vwChatroom");
        $dataCount = $dbChatroom->count($filter);
        $dbChatroom->reset();

        $dataFilter = new stdClass();
        $dataFilter->dataCount = $dataCount;
        $dataFilter->filter = $filter;
        $dataFilter->order = $order;

        $dbChatroom = $dbChatroom->findWhere($filter, '', $limit, $offset);

        for ($i = 0; $i < count($dbChatroom); $i++) {
            $lastMessage = new GenericModel($this->db, "chatroomDetail");
            $lastMessage->getWhere("chatroomId = '{$dbChatroom[$i]['id']}' ", 'id DESC', 1);
            $dbChatroom[$i]['content'] = $lastMessage->content;
            $dbChatroom[$i]['messageCreatedAt'] = $lastMessage->createdAt;
            $dbChatroom[$i]['type'] = $lastMessage->type;
            $dbChatroom[$i]['userSenderId'] = $lastMessage->userSenderId;
            $dbChatroom[$i]['entitySenderId'] = $lastMessage->entitySenderId;
        }

        $response['dataFilter'] = $dataFilter;
        $response['data'] = $dbChatroom;

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_order')), $response);
    }

    public function getMessageList()
    {
        $limit = 10;
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];
        if (!is_numeric($limit))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Limit')), null);

        $offset = 0;
        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (!is_numeric($offset))
            $this->sendError(Constants::HTTP_BAD_REQUEST, $this->f3->get('RESPONSE.400_paramInvalid', $this->f3->get('RESPONSE.entity_Offset')), null);

        if (!$this->f3->get('PARAMS.id') || !is_numeric($this->f3->get('PARAMS.id')))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_chatRoomId')), null);

        $chatRoomId = addslashes($this->f3->get('PARAMS.id'));

        $dbChatRoom = new GenericModel($this->db, "chatroom");
        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $dbChatRoom->getWhere("id = '$chatRoomId' AND entityBuyerId IN ($arrEntityId)");

        if ($dbChatRoom->dry())
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_chatRoom')), null);


        $dbChatMessage = new GenericModel($this->db, "chatroomDetail");
        $chats = $dbChatMessage->findWhere("chatroomId = '$chatRoomId' ", '', $limit, $offset);


        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.200_listFound', $this->f3->get('RESPONSE.entity_message')), $chats);
    }

    public function postSetMessagesUnread()
    {
        if (!$this->requestData->chatroomId || !is_numeric($this->requestData->chatroomId))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_chatRoomId')), null);

        $chatRoomId = addslashes($this->requestData->chatroomId);

        $dbChatRoom = new GenericModel($this->db, "chatroom");
        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $dbChatRoom->getWhere("id = '$chatRoomId' AND entityBuyerId IN ($arrEntityId)");

        if ($dbChatRoom->dry())
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_chatRoom')), null);

        $unreadMessagesDb = new GenericModel($this->db, "chatroomDetail");
        $unreadMessagesDb->getWhere("isReadBuyer=1 AND chatroomId={$chatRoomId} AND entityReceiverId IN ($arrEntityId)");
        while (!$unreadMessagesDb->dry()) {
            $unreadMessagesDb->isReadBuyer = 0;
            $unreadMessagesDb->update();
            $dbChatRoom->pendingReadBuyer++;
            $unreadMessagesDb->next();
        }

        $dbChatRoom->isReadBuyer = 0;

        if (!$dbChatRoom->update())
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_updated', $this->f3->get('RESPONSE.entity_message')), null);
    }

    public function postSetMessagesRead()
    {
        if (!$this->requestData->chatroomId || !is_numeric($this->requestData->chatroomId))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_chatRoomId')), null);

        $chatRoomId = addslashes($this->requestData->chatroomId);

        $dbChatRoom = new GenericModel($this->db, "chatroom");
        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $dbChatRoom->getWhere("id = '$chatRoomId' AND entityBuyerId IN ($arrEntityId)");

        if ($dbChatRoom->dry())
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_chatRoom')), null);

        $unreadMessagesDb = new GenericModel($this->db, "chatroomDetail");
        $unreadMessagesDb->getWhere("isReadBuyer=0 AND chatroomId={$chatRoomId} AND entityReceiverId IN ($arrEntityId)");
        while (!$unreadMessagesDb->dry()) {
            $unreadMessagesDb->isReadBuyer = 1;
            $unreadMessagesDb->update();
            $dbChatRoom->pendingReadBuyer--;
            $unreadMessagesDb->next();
        }

        $dbChatRoom->isReadBuyer = 1;

        if (!$dbChatRoom->update())
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_updated', $this->f3->get('RESPONSE.entity_message')), null);
    }

    public function postSetChatRoomArchive()
    {
        if (!$this->requestData->chatroomId || !is_numeric($this->requestData->chatroomId))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_chatRoomId')), null);

        $chatRoomId = addslashes($this->requestData->chatroomId);

        $dbChatRoom = new GenericModel($this->db, "chatroom");
        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $dbChatRoom->getWhere("id = '$chatRoomId' AND entityBuyerId IN ($arrEntityId)");

        if ($dbChatRoom->dry())
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_chatRoom')), null);

        $dbChatRoom->archivedBuyerAt = date('Y-m-d H:i:s');
        $dbChatRoom->isArchivedBuyer = 1;

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

        $chatRoomId = addslashes($this->requestData->chatroomId);
        $message = addslashes($this->requestData->message);

        $dbChatRoom = new GenericModel($this->db, "chatroom");
        $arrEntityId = Helper::idListFromArray($this->objEntityList);
        $dbChatRoom->getWhere("id = '$chatRoomId' AND entityBuyerId IN ($arrEntityId)");

        if ($dbChatRoom->dry())
            $this->sendError(Constants::HTTP_NOT_FOUND, $this->f3->get('RESPONSE.404_itemNotFound', $this->f3->get('RESPONSE.entity_chatRoom')), null);

        $dbChatRoom->pendingReadSeller++;
        $dbChatRoom->isReadSeller = 0;
        $dbChatRoom->updatedAt = date('Y-m-d H:i:s');
        $dbChatRoom->isArchivedBuyer = 0;
        $dbChatRoom->archivedBuyerAt = null;

        if (!$dbChatRoom->update())
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);

        $dbChatMessage = new GenericModel($this->db, "chatroomDetail");
        $dbChatMessage->chatroomId = $dbChatRoom->id;
        $dbChatMessage->userSenderId = $this->objUser->id;
        $dbChatMessage->entitySenderId = $dbChatRoom->entityBuyerId;
        $dbChatMessage->entityReceiverId = $dbChatRoom->entitySellerId;
        $dbChatMessage->type = 1;
        $dbChatMessage->content = $message;
        $dbChatMessage->isReadBuyer = 1;
        $dbChatMessage->isReadSeller = 0;
        if (!$dbChatMessage->add())
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatMessage->exception, null);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_message')), null);
    }

    public function postNewChatRoom()
    {
        if (!$this->requestData->entitySellerId || !is_numeric($this->requestData->entitySellerId))
            $this->sendError(Constants::HTTP_FORBIDDEN, $this->f3->get('RESPONSE.400_paramMissing', $this->f3->get('RESPONSE.entity_sellerId')), null);

        $entitySellerId = addslashes($this->requestData->entitySellerId);

        $arrEntityId = Helper::idListFromArray($this->objEntityList);

        $dbChatRoom = new GenericModel($this->db, "chatroom");
        $dbChatRoom->entitySellerId = $entitySellerId;
        $dbChatRoom->entityBuyerId = $arrEntityId[0];
        $dbChatRoom->pendingReadSeller = 0;
        $dbChatRoom->pendingReadBuyer = 0;
        $dbChatRoom->updatedAt = date('Y-m-d H:i:s');
        if (!$dbChatRoom->add())
            $this->sendError(Constants::HTTP_FORBIDDEN, $dbChatRoom->exception, null);

        $this->sendSuccess(Constants::HTTP_OK, $this->f3->get('RESPONSE.201_added', $this->f3->get('RESPONSE.entity_chatRoom')), null);
    }
}
