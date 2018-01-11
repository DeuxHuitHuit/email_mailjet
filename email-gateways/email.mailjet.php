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
            $from_email = Symphony::Configuration()->get('from_address', self::SETTINGS_GROUP);
            $this->setSenderEmailAddress($from_email);
        }
        
        if (empty($this->_sender_name)) {
            $from_name = Symphony::Configuration()->get('from_name', self::SETTINGS_GROUP);
            $this->setSenderName($from_name);
        }
        
        $this->validate();
        
        $apiKeyPub = Symphony::Configuration()->get('api_key_pub', self::SETTINGS_GROUP);
        $apiKeyPri = Symphony::Configuration()->get('api_key_pri', self::SETTINGS_GROUP);
        $mj = new \Mailjet\Client($apiKeyPub,  $apiKeyPri, true, ['version' => 'v3.1']);
        $body = ['Messages' => []];
        
        // Send individual emails
        foreach ($this->_recipients as $name => $address) {
            $body['Messages'][] =[
                'From' => [
                    'Email' => $from_email,
                    'Name' => $from_name
                ],
                'To' => [[
                    'Email' => $address,
                    'Name' => is_numeric($name) ? $address : $name,
                ]],
                'Subject' => $this->_subject,
                'TextPart' => $this->_text_plain,
                'HTMLPart' => $this->_text_html,
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
        $group->setAttribute('id', 'sendgrid');

        $div = new XMLElement('div');
        $div->setAttribute('class', 'columns two');

        $label = Widget::Label(__('From Name'));
        $label->setAttribute('class', 'column');
        $label->appendChild(Widget::Input('settings[email_mailjet][from_name]', Symphony::Configuration()->get('from_name', self::SETTINGS_GROUP)));
        $div->appendChild($label);

        $label = Widget::Label(__('From Address'));
        $label->setAttribute('class', 'column');
        $label->appendChild(Widget::Input('settings[email_mailjet][from_address]', Symphony::Configuration()->get('from_address', self::SETTINGS_GROUP)));
        $div->appendChild($label);

        $label = Widget::Label(__('Public API Key'));
        $label->setAttribute('class', 'column');
        $label->appendChild(Widget::Input('settings[email_mailjet][api_key_pub]', Symphony::Configuration()->get('api_key_pub', self::SETTINGS_GROUP)));
        $div->appendChild($label);

        $label = Widget::Label(__('Private API Key'));
        $label->setAttribute('class', 'column');
        $label->appendChild(Widget::Input('settings[email_mailjet][api_key_pri]', Symphony::Configuration()->get('api_key_pri', self::SETTINGS_GROUP)));
        $div->appendChild($label);

        $group->appendChild($div);

        return $group;
    }
}
