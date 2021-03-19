<?php

namespace App\Models;

use App\Libraries\BonusHelper;
use Constants;

class CartDetail extends Model
{
    protected $table = 'cartDetail';
    protected $fieldConf = [
        'entityProductId' => [
            'belongs-to-one' => EntityProductSell::class,
        ],
        'accountId' => [
            'belongs-to-one' => Account::class,
        ],
        'userId' => [
            'belongs-to-one' => User::class,
        ]
    ];

    public function create($data)
    {
        $data = $this->initializeMissingKeys($data);
        if ($this->check($data) !== true) {
            $this->response['statusCode'] = Constants::HTTP_BAD_REQUEST;
            $this->response['message'] = 'Validation Failed';
            return $this;
        }

        foreach ($data as $parameter => $value) {
            $this->$parameter = $value;
        }

        $this->unitPrice = $this->entityProductId->unitPrice;
        $this->vat = $this->entityProductId->vat;
        $bonusDetail = BonusHelper::calculateBonusQuantity(
            \Base::instance(), 
            $this->db, 
            $this->userId->language, 
            $this->entityProductId->productId->id, 
            $this->quantity,
            $this->pluck($this->userId->accounts, 'entityId.id')
        );
        $this->quantityFree = $bonusDetail->quantityFree;
        $total = $bonusDetail->total;

        $total = $this->quantityFree + $this->quantity;
        if ($total > $bonusDetail->maxOrder) {
            $this->errors[] = ['stock' => $this->f3->get('RESPONSE.400_lowStock', $this->entityProductId->stock)];
            $this->response['statusCode'] = Constants::HTTP_BAD_REQUEST;
            $this->response['message'] = $this->f3->get('RESPONSE.400_lowStock', $this->entityProductId->stock);
            return $this;
        }

        return $this->save();
    }

    private function calculateBonus($quantity, $bonuses, $formula)
    {
        foreach ($bonuses as $bonus) {
            if ($quantity >= $bonus['minOrder']) {
                $formula = str_replace('quantity', $quantity, $formula);
                $formula = str_replace('minOrder', $bonus['minOrder'], $formula);
                $formula = str_replace('bonus', $bonus['bonus'], $formula);
                if (strpos($formula, ';') === false) {
                    $formula .= ';';
                }
                $formula = '$response = ' . $formula;
                eval($formula);
                return $response;
            }
        }
        return 0;
    }

    public function getRules()
    {
        return [
            'userId' => 'required|numeric|exists,id,user',
            'accountId' => 'required|numeric|exists,id,account',
            'entityProductId' => 'required|numeric|exists,id,entityProductSell',
            'quantity' => 'numeric',
        ];
    }
}
