<?php
if (!defined('_PS_VERSION_')) {
  exit;
}


const RANGE_FROM = "price_range_from";
const RANGE_TO = "price_range_to";


class PriceRange extends Module
{
  public function __construct()
    {
        $this->name = 'pricerange';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Viktor Bachmanov';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => '1.7.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Price Range');
        $this->description = $this->l('Show products amount within price range');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

    }

    public function install()
    {    
       return (
            parent::install() 
            && $this->registerHook('displayFooter')
            && Configuration::updateValue(RANGE_FROM, 10)
            && Configuration::updateValue(RANGE_TO, 50)
        ); 
    }

    public function uninstall()
    {
      return (
          parent::uninstall() 
          && Configuration::deleteByName(RANGE_FROM)
          && Configuration::deleteByName(RANGE_TO)
      );
    }

    /**
     * This method handles the module's configuration page
     * @return string The page's HTML content 
     */
    public function getContent()
    {
        $output = '';

        // this part is executed only when the form is submitted
        if (Tools::isSubmit('submit' . $this->name)) {
            // retrieve the value set by the user
            $rangeFrom = (string) Tools::getValue(RANGE_FROM);
            $rangeTo = (string) Tools::getValue(RANGE_TO);

            // check that the value is valid
            if (empty($rangeFrom) || !Validate::isGenericName($rangeFrom)) {
                // invalid value, show an error
                $output = $this->displayError($this->l('Invalid Configuration value'));
            } else if (empty($rangeTo) || !Validate::isGenericName($rangeTo)) {
              // invalid value, show an error
              $output = $this->displayError($this->l('Invalid Configuration value'));
            } else {
                // value is ok, update it and display a confirmation message
                Configuration::updateValue(RANGE_FROM, $rangeFrom);
                Configuration::updateValue(RANGE_TO, $rangeTo);
                $output = $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        // display any message, then the form
        return $output . $this->displayForm();
    }

    /**
     * Builds the configuration form
     * @return string HTML code
     */
    public function displayForm()
    {
        // Init Fields form array
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Price range from'),
                        'name' => RANGE_FROM,
                        'size' => 20,
                        'required' => true,
                    ],
                    [
                      'type' => 'text',
                      'label' => $this->l('Price range to'),
                      'name' => RANGE_TO,
                      'size' => 20,
                      'required' => true,
                  ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        // Default language
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        // Load current value into the form
        $helper->fields_value[RANGE_FROM] = Tools::getValue(RANGE_FROM, Configuration::get(RANGE_FROM));
        $helper->fields_value[RANGE_TO] = Tools::getValue(RANGE_TO, Configuration::get(RANGE_TO));

        return $helper->generateForm([$form]);
    }


  public function hookDisplayFooter()
  {
    $from = (int) Tools::getValue(RANGE_FROM, Configuration::get(RANGE_FROM));
    $to = (int) Tools::getValue(RANGE_TO, Configuration::get(RANGE_TO));

    $productsAmount = $this->getProductsAmountWithinPriceRange($from, $to);

    $this->context->smarty->assign([
      'from' => $from,
      'to' => $to,
      'productsAmount' => $productsAmount,
    ]);

      return $this->display(__FILE__, 'products_amount.tpl');
  }

  private function getProductsAmountWithinPriceRange(int $from, int $to) {
    $db = \Db::getInstance();

    $request = "SELECT COUNT(*) FROM " . _DB_PREFIX_ .  "product WHERE price BETWEEN $from AND $to";
    
    $amount = $db->getValue($request);

    return $amount;
  }
}