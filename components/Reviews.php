<?php namespace SamPoyigi\Account\Components;

use Admin\Models\Orders_model;
use Admin\Models\Reservations_model;
use Admin\Models\Reviews_model;
use Auth;
use Redirect;

class Reviews extends \System\Classes\BaseComponent
{
    public $saleIdParam;

    public $saleTypeParam;

    public function defineProperties()
    {
        return [
            'pageNumber'               => [
                'label' => 'Page Number',
                'type'  => 'string',
            ],
            'itemsPerPage'             => [
                'label'   => 'Items Per Page',
                'type'    => 'number',
                'default' => 20,
            ],
            'sortOrder'                => [
                'label' => 'Sort order',
                'type'  => 'string',
            ],
            'ordersRedirectPage'       => [
                'label'   => 'Orders Page',
                'type'    => 'string',
                'default' => 'account/orders',
            ],
            'reservationsRedirectPage' => [
                'label'   => 'Reservations Page',
                'type'    => 'string',
                'default' => 'account/reservations',
            ],
        ];
    }

    public function onRun()
    {
        $this->page['ordersPage'] = $this->property('ordersPage');
        $this->page['showReviews'] = setting('allow_reviews') == 1;
        $this->page['customerReviews'] = $this->loadReviews();

        $customerId = ($customer = Auth::customer()) ? $customer->getKey() : null;
        $this->page['saleIdParam'] = $this->saleIdParam = $this->param('saleId');
        $this->page['saleTypeParam'] = $this->saleTypeParam = $this->param('saleType');
        $this->page['reviewSale'] = $model = $this->getSaleModel();

        if ($this->saleIdParam AND !$model) {
            flash()->warning(lang('sampoyigi.account::default.reviews.alert_review_status_history'))->now();

            return Redirect::to($this->makeRedirectUrl());
        }

        if ($this->saleIdParam AND Reviews_model::hasBeenReviewed($model, $customerId)->first()) {
            flash()->set('danger', lang('sampoyigi.account::default.reviews.alert_review_duplicate'))->now();

            return Redirect::to($this->makeRedirectUrl());
        }
    }

    protected function loadReviews()
    {
        if (!$customer = Auth::customer())
            return [];

        return Reviews_model::listFrontEnd([
            'page'      => $this->param('page'),
            'pageLimit' => $this->property('itemsPerPage'),
            'sort'      => $this->property('sortOrder', 'date_added desc'),
            'customer'  => $customer,
        ]);
    }

    protected function getSaleModel()
    {
        $statusExists = $model = null;
        if ($this->saleTypeParam == 'reservation') {
            $model = Reservations_model::find($this->saleIdParam);
            $statusExists = $model->status_history()
                                  ->where('status_id', setting('confirmed_reservation_status'))
                                  ->first();
        }
        elseif ($this->saleTypeParam == 'order') {
            $model = Orders_model::find($this->saleIdParam);
            $statusExists = $model->status_history()
                                  ->where('status_id', setting('completed_order_status'))
                                  ->first();
        }

        if (!$statusExists) {
            return null;
        }

        return $model;
    }

    protected function makeRedirectUrl()
    {
        return $this->property($this->saleTypeParam.'sRedirectPage');
    }
}