<?php
namespace Grav\Plugin;

class AssetContainer
{
    public $data = [];

    public function add($action, $asset)
    {
        if (is_array($action)) {
            $this->data['add'] []= $action;
        } else {
            $this->data[$action] []= $asset;
        }
    }

    public function get() {
        return $this->data;
    }

}