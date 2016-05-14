<?php namespace Filipac\Banip\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use filipac\Banip\Models\Ip;
use Illuminate\Html\HtmlFacade;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use October\Rain\Support\Facades\Flash;

/**
 * Ips Back-end Controller
 */
class Ips extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Filipac.Banip', 'banip', 'ips');
    }

    /**
     * Deleted checked users.
     */
    public function index_onDelete()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {

            foreach ($checkedIds as $ip) {
                if (!$ip = Ip::find($ip)) continue;
                $ip->delete();
            }

            Flash::success('The IP was unbanned!');
        }
        else {
            Flash::error('Please select something!');
        }

        return $this->listRefresh();
    }

    public function index_onBtnAdd() {
        $this->suppressLayout = true;
        $this->initForm((new Ip()));
        return $this->makeView('create');
    }


    public function index_onSave(){
        $this->listConfig = 'config_list.yaml';
        $this->makeLists();
        $v = Validator::make(Input::all(), [
         'Ip.address' => 'required|ip|unique:filipac_banip_ips,address',
         'Ip.mask' => 'required|integer|min:1|max:32',
        ], [
            'Ip.address.required' => 'Please enter an valid IP Address to ban',
            'Ip.address.ip' => 'Please enter an valid IP Address to ban',
            'Ip.address.unique' => 'Please enter an unique IP Address to ban',
            'Ip.mask.integer' => 'Please enter an integer value between 1 and 32',
            'Ip.mask.min' => 'Please enter an integer value between 1 and 32',
            'Ip.mask.max' => 'Please enter an integer value between 1 and 32',
        ]);
        if($v->fails()) {
            Flash::error(implode('<br>',$v->messages()->all()));
            return false;
        }
        $ip = new Ip;
        $ip->address = Input::all()['Ip']['address'];
        $ip->mask = Input::all()['Ip']['mask'];
        $ip->save();
        Flash::success('The IP was banned.');
        return ['success'=>true] + $this->listRefresh('Lists');
    }
}