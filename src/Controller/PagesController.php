<?php

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\Cache\Cache;

/**
 * @property \App\Model\Table\UsersTable $Users
 * @property \App\Model\Table\PagesTable $Pages
 */
class PagesController extends FrontController
{
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Auth->allow(['home', 'view', 'contact']);
    }

    public function contact()
    {
    }

    public function home()
    {
        $this->loadModel('Users');

        /*
          $todayClicks = $this->Users->Statistics->find()
          ->where([
          'DATE(Statistics.created) = CURDATE()'
          ])
          ->count();
          $this->set('todayClicks', $todayClicks);
         */

        $lang = locale_get_default();

        if (($totalLinks = Cache::read('home_totalLinks_' . $lang, '1hour')) === false) {
            $totalLinks = $this->Users->Links->find()
                ->count();

            $totalLinks += (int)get_option('fake_links', 0);

            $totalLinks = display_price_currency($totalLinks, [
                'places' => 0,
                'before' => '',
                'after' => '',
            ]);

            if (get_option('cache_home_counters', 1)) {
                Cache::write('home_totalLinks_' . $lang, $totalLinks, '1hour');
            }
        }
        $this->set('totalLinks', $totalLinks);

        if (($totalClicks = Cache::read('home_totalClicks_' . $lang, '1hour')) === false) {
            $totalClicks = $this->Users->Statistics->find()->count();

            $totalClicks += (int)get_option('fake_clicks', 0);

            $totalClicks = display_price_currency($totalClicks, [
                'places' => 0,
                'before' => '',
                'after' => '',
            ]);

            if (get_option('cache_home_counters', 1)) {
                Cache::write('home_totalClicks_' . $lang, $totalClicks, '1hour');
            }
        }
        $this->set('totalClicks', $totalClicks);

        if (($totalUsers = Cache::read('home_totalUsers_' . $lang, '1hour')) === false) {
            $totalUsers = $this->Users->find()
                ->count();

            $totalUsers += (int)get_option('fake_users', 0);

            $totalUsers = display_price_currency($totalUsers - 1, [
                'places' => 0,
                'before' => '',
                'after' => '',
            ]);

            if (get_option('cache_home_counters', 1)) {
                Cache::write('home_totalUsers_' . $lang, $totalUsers, '1hour');
            }
        }
        $this->set('totalUsers', $totalUsers);
    }

    public function view($slug = null)
    {
        if (!$slug) {
            throw new NotFoundException(__('Invalid Page.'));
        }

        $page = $this->Pages->find()->where(['slug' => $slug, 'published' => 1])->first();

        if (!$page) {
            throw new NotFoundException(__('Invalid Page.'));
        }

        if (strpos($page->content, '[advertising_rates]') !== false) {
            $view = new \Cake\View\View($this->getRequest(), $this->getResponse());
            $advertising_rates = $view->element('advertising_rates');
            $page->content = str_replace('[advertising_rates]', $advertising_rates, $page->content);
        }

        if (strpos($page->content, '[payout_rates]') !== false) {
            $view = new \Cake\View\View($this->getRequest(), $this->getResponse());
            $payout_rates = $view->element('payout_rates');
            $page->content = str_replace('[payout_rates]', $payout_rates, $page->content);
        }

        if (strpos($page->content, '[payment_proof]') !== false) {
            $withdrawsTable = \Cake\ORM\TableRegistry::getTableLocator()->get('Withdraws');

            $query = $withdrawsTable->find()
                ->contain([
                    'Users' => [
                        'fields' => ['id', 'username'],
                    ],
                ])
                ->select([
                    'Withdraws.id',
                    'Withdraws.created',
                    'Withdraws.amount',
                    'Withdraws.method',
                    'Withdraws.user_id',
                ])
                ->where(["Withdraws.status IN (3)"])
                ->orderDesc('Withdraws.id');
            $withdraws = $this->paginate($query);

            $view = new \Cake\View\View($this->getRequest(), $this->getResponse());
            $payment_proof = $view->element('payment_proof', ['withdraws' => $withdraws]);
            $page->content = str_replace('[payment_proof]', $payment_proof, $page->content);
        }

        $this->set('page', $page);
    }
}
