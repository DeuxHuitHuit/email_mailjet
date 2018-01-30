<?php

require_once(EXTENSIONS . '/email_mailjet/vendor/autoload.php');

class MailjetGateway extends EmailGateway
{
    const SETTINGS_GROUP = 'email_mailjet';
    
    public function about()
    {
        return array(
            'name' => 'Mailjet',
        );
    }

    public function send()
    {

        if (empty($this->_sender_email_address)) {
            $this->setSenderEmailAddress(Symphony::Configuration()->get('from_address', self::SETTINGS_GROUP));
        }
        
        if (empty($this->_sender_name)) {
            $this->setSenderName(Symphony::Configuration()->get('from_name', self::SETTINGS_GROUP));
        }

        $this->validate();
        
        $apiKeyPub = Symphony::Configuration()->get('api_key_pub', self::SETTINGS_GROUP);
        $apiKeyPri = Symphony::Configuration()->get('api_key_pri', self::SETTINGS_GROUP);
        $mj = new \Mailjet\Client($apiKeyPub,  $apiKeyPri, true, ['version' => 'v3.1']);
        $body = ['Messages' => []];

        // Send individual emails
        foreach ($this->_recipients as $name => $address) {
            $body['Messages'][] = [
                'From' => [
                    'Email' => $this->_sender_email_address,
                    'Name' => $this->_sender_name
                ],
                'To' => [[
                    'Email' => $address,
                    'Name' => is_numeric($name) ? $address : $name,
                ]],
                'Subject' => $this->_subject,
                'TextPart' => empty($this->_text_plain) ? strip_tags($this->_text_html) : $this->_text_plain,
                'HTMLPart' => empty($this->_text_html) ? '' : $this->_text_plain,
            ];
        }
        
        // Send them
        $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);

        return $response->success();
    }

    /**
     * The preferences to add to the preferences pane in the admin area.
     *
     * @return XMLElement
     */
    public function getPreferencesPane()
    {
        $group = new XMLElement('fieldset');
        $group->setAttribute('class', 'settings condensed pickable');
        $group->setAttribute('id', 'email_mailjet');

        $div = new XMLElement('div');
        $div->setAttribute('class', 'columns two');

        $label = Widget::Label(__('From Name'));
        $label->setAttribute('class', 'column');
        $label->appendChild(Widget::Input('settings[email_mailjet][from_name]', General::sanitize(Symphony::Configuration()->get('from_name', self::SETTINGS_GROUP))));
        $div->appendChild($label);

        $label = Widget::Label(__('From Address'));
        $label->setAttribute('class', 'column');
        $label->appendChild(Widget::Input('settings[email_mailjet][from_address]', General::sanitize(Symphony::Configuration()->get('from_address', self::SETTINGS_GROUP))));
        $div->appendChild($label);

        $label = Widget::Label(__('Public API Key'));
        $label->setAttribute('class', 'column');
        $label->appendChild(Widget::Input('settings[email_mailjet][api_key_pub]', General::sanitize(Symphony::Configuration()->get('api_key_pub', self::SETTINGS_GROUP))));
        $div->appendChild($label);

        $label = Widget::Label(__('Private API Key'));
        $label->setAttribute('class', 'column');
        $label->appendChild(Widget::Input('settings[email_mailjet][api_key_pri]', General::sanitize(Symphony::Configuration()->get('api_key_pri', self::SETTINGS_GROUP))));
        $div->appendChild($label);

        $group->appendChild($div);

        return $group;
    }
}
