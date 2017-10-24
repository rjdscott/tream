<?php
/**
 * This file is part of P4A - PHP For Applications.
 *
 * P4A is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 * 
 */

/**
 * @author Timon Zielonka <timon@zukunft.com>
 * @copyright Copyright (c) 2010-2013 Timon Zielonka 
 */
class Trade_funds extends P4A_Base_Mask
{
	public $toolbar = null;
	public $table = null;
	public $fs_details = null;
	
	public function __construct()
	{
		parent::__construct();
		$p4a = p4a::singleton();

		$this->setTitle("Fund trades");

		// build main source here to be able to filter
		$this->build("p4a_db_source", "trades")
			->setTable("trades")
			->addOrder("creation_time")
			->addJoinLeft("portfolios", "trades.portfolio_id = portfolios.portfolio_id",
					  array('portfolio_name'=>'portfolio'))
			->addJoinLeft("v_securities", "trades.security_id  = v_securities.security_id",
					  array('name'=>'security','ISIN'=>'ISIN','last_price'=>'last','type_code_id'=>'sec_type_id'))
			->addJoinLeft("v_trade_security", "trades.trade_id  = v_trade_security.trade_id",
					  array('security_name'=>'security_name')) 
			->addJoinLeft("currencies", "trades.currency_id  = currencies.currency_id",
					  array('symbol'=>'ccy'))
			->addJoinLeft("trade_types", "trades.trade_type_id  = trade_types.trade_type_id",
					  array('description'=>'trade_type'))
			->addJoinLeft("trade_stati", "trades.trade_status_id  = trade_stati.trade_status_id",
					  array('status_text'=>'status'))
			->setWhere("trades.security_id is Not Null AND (v_securities.type_code_id = 'fund' OR v_securities.type_code_id = 'ETF')") 
			->setPageLimit(20)
			->load();

		// build sub sources here to be able to filter without influencing other masks
		$this->build("p4a_db_source", "log_trades")
			->setTable("log_data")
			->addOrder("log_time")
			->setWhere("log_data.table_name = 'trades'")
			->load();

		$this->build("p4a_db_source", "trade_payments")
			->setTable("trade_payments")
			->addOrder("amount")
			->addJoinLeft("trade_payment_types", "trade_payments.trade_payment_type_id  = trade_payment_types.trade_payment_type_id",
					  array('type_name'=>'type'))
			->load();

		// for filtered selections
		$this->build("p4a_db_source", "security_fund")
			->setTable("v_securities")
			->setWhere("(v_securities.type_code_id = 'fund' OR v_securities.type_code_id = 'ETF')") 
			->addOrder("select_name")
			->setPK("security_id")
			->load();

		// data sources to calculate the trade values
		$this->build("p4a_db_source", "user_data")
			->setTable("log_users")
			->load();

		$this->build("p4a_db_source", "sec_data")
			->setTable("securities")
			->load();

		$this->build("p4a_db_source", "portfolio_data")
			->setTable("portfolios")
			->load();

		$this->build("p4a_db_source", "fx_data")
			->setTable("v_currency_price_feed")
			->load();

		$this->build("p4a_db_source", "status_data")
			->setTable("trade_stati")
			->load();

		$this->setSource($this->trades);
		$this->firstRow();

		// Customizing fields properties
		$this->fields->date_placed
			->setLabel("Placed at")
			->setTooltip("time when the order has been placed at the markets");
		$this->fields->date_client
			->setLabel("Accounting date")
			->setTooltip("at the moment only used by Bank Baer");

		$this->fields->account_id
			->setLabel("Account")
			->setType("select")
			->setSource(P4A::singleton()->select_accounts)
			->setSourceDescriptionField("account_select_name"); 

		$this->fields->portfolio_id
			->setLabel("Portfolio")
			->setType("select")
			->setSource(P4A::singleton()->select_portfolios)
			->setSourceDescriptionField("portfolio_select_name"); 

		$this->fields->internal_person_id
			->setLabel("Internal Person")
			->setType("select")
			->setSource(P4A::singleton()->internal_persons)
			->setSourceDescriptionField("select_name");

		$this->fields->currency_id
			->setLabel("Currency")
			->setType("select")
			->setSource(P4A::singleton()->select_currencies)
			->setSourceDescriptionField("symbol");

		$this->fields->settlement_currency_id
			->setLabel("Settlement curr")
			->setType("select")
			->setSource(P4A::singleton()->select_currencies)
			->setSourceDescriptionField("symbol");

		$this->fields->security_id
			->setLabel("Security")
			->setType("select")
			->setSource($this->security_fund)
			->setSourceDescriptionField("select_name");

		$this->fields->trade_type_id
			->setLabel("Trade type")
			->setType("select")
			->setSource(P4A::singleton()->trade_types_fund)
			->setSourceDescriptionField("description"); 

		$this->fields->trade_status_id
			->setLabel("Trade status")
			->setType("select")
			->setSource(P4A::singleton()->select_trade_stati)
			->setSourceDescriptionField("status_text"); 

		$this->fields->trade_confirmation_type_id
			->setLabel("Bank Contact type")
			->setType("select")
			->setSource(P4A::singleton()->select_trade_confirmation_types)
			->setSourceDescriptionField("type_name"); 

		$this->fields->contact_type_id
			->setLabel("Client contact type")
			->setType("select")
			->setSource(P4A::singleton()->select_contact_types)
			->setSourceDescriptionField("type_name");

		$this->fields->related_trade_id
			->setLabel("Releated trade")
			->setType("select")
			->setSource(P4A::singleton()->trade_select)
			->setSourceDescriptionField("trade_key");

		$this->fields->scanned_bank_confirmation->setType("file");
		
		$this->fields->price->setLabel("Trade price");
		$this->fields->trade_confirmation_person->setLabel("Bank Contact");
		$this->fields->confirmation_time->setLabel("time placed at bank");
		$this->fields->premium_sett->setLabel("Premium in settlement currency");
		$this->fields->premium_sett_netto->setLabel("Netto premium in settlement currency");

		$this->setRequiredField("rational");

		$this->fields->rational->setWidth(500);
		$this->fields->comment->setWidth(500);

		//$this->build("p4a_field", "loguser")->setLabel($p4a->menu->items->loguser->getLabel());

		$this->fields->creation_time->enable(false);

		$this->build("p4a_Label", "info_client")->setLabel("Client contact");
		$this->build("p4a_Label", "info_optional")->setLabel("Optional parameter");
		$this->build("p4a_Label", "info_process")->setLabel("Trade processing");
		$this->build("p4a_Label", "info_overwrite")->setLabel("Automatic (can overwrite)");

		// set default values
		$this->trades->fields->valid_until->setDefaultValue(date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+3, date("Y"))));
/*		$this->fields->internal_person_id
			->setLabel("TP Person"); */

		// Search Fieldset
		$this->build("p4a_field", "txt_search")
			->setLabel("Fund name")
			->implement("onreturnpress", $this, "search");
		$this->build("p4a_button", "cmd_search")
			->setLabel("Go")
			->implement("onclick", $this, "search");
		$this->build("p4a_fieldset", "fs_search")
			->setLabel("Search")
			->anchor($this->txt_search)
			->anchorLeft($this->cmd_search);

		$this->build("p4a_full_toolbar", "toolbar")
			->setMask($this);

		/* usually a record does not need to be deleted */
		$this->toolbar->buttons->delete->disable();

		$this->build("p4a_table", "table")
			->setSource($this->trades)
			->setVisibleCols(array("trade_date","portfolio","trade_type","size","ccy","status","checked"))
			->setWidth(1200)
			->showNavigationBar();

		$this->build("p4a_table", "table_log")
			->setSource($this->log_trades)
			->setWidth(500)
			->setVisibleCols(array("log_time","user_name","field_name","old_value","new_value"))
			->showNavigationBar(); 
		$this->log_trades->addFilter("row_id = ?", $this->trades->fields->trade_id); 

		$this->build("p4a_table", "table_trade_payments")
			->setSource($this->trade_payments)
			->setWidth(500)
			->setVisibleCols(array("amount","type"))
			->showNavigationBar(); 
		$this->trade_payments->addFilter("trade_id = ?", $this->trades->fields->trade_id); 

		$this->build("p4a_fieldset", "fs_details") /* simular in open today, so please copy updates */
			->setLabel("Trade detail") 
			/* the main fields to enter the trade */
			->anchor($this->fields->portfolio_id)   /* preselect based on account */
			->anchor($this->fields->trade_type_id)
			->anchorLeft($this->fields->size)
			->anchorLeft($this->fields->security_id)
			->anchorLeft($this->fields->price)
			->anchor($this->fields->rational)

			/* client communication */
			->anchor($this->info_client)
			->anchor($this->fields->contact_type_id)
			->anchorLeft($this->fields->internal_person_id) /* automatically filed */
			->anchor($this->fields->comment)

			/* additional parameters for the trade with automatically set fields that can be overwritten*/
			->anchor($this->info_optional)
			->anchor($this->fields->trade_date) /* time when the trade was executed */
			->anchorLeft($this->fields->currency_id) /* set automatically by the exchange */
			->anchor($this->fields->valid_until)
			->anchorLeft($this->fields->settlement_currency_id) /* set automatically by the portfolio, but can be overwritten */
			->anchorLeft($this->fields->fx_rate)
			//->anchor($this->fields->security_exchange)
			->anchor($this->fields->premium)
			->anchorLeft($this->fields->premium_sett)
			->anchorLeft($this->fields->fees_internal)

			/* fields to track the processing */
			->anchor($this->info_process)
			->anchor($this->fields->trade_status_id)
			->anchorLeft($this->fields->related_trade_id)
			->anchor($this->fields->confirmation_time) 
			->anchorLeft($this->fields->trade_confirmation_type_id)
			->anchorLeft($this->fields->trade_confirmation_person)
			//->anchorLeft($this->fields->settlement_date)
			;
		
		$this->frame
			->anchor($this->fs_search)
			->anchor($this->table)
 			->anchor($this->fs_details)
 			->anchorLeft($this->table_trade_payments);

		$this
			->display("menu", $p4a->menu)
			->display("top", $this->toolbar)
			->setFocus($this->fields->rational);
	}
	public function search()
	{
		$value = $this->txt_search->getSQLNewValue();
		$sec_selector = "(v_securities.type_code_id = 'fund' OR v_securities.type_code_id = 'ETF')";
		if ($value == '') {
			$this->trades
				->setWhere("trades.security_id is Not Null AND ".$sec_selector) 
				->firstRow();
			$this->security_fund
				->setWhere($sec_selector)
				->firstRow();
		} else {	
			$this->trades
				->setWhere(P4A_DB::singleton()->getCaseInsensitiveLikeSQL('v_securities.ISIN', "%{$value}%")." AND ".$sec_selector)
				->firstRow();
			$this->security_fund
				->setWhere(P4A_DB::singleton()->getCaseInsensitiveLikeSQL('v_securities.ISIN', "%{$value}%")." AND ".$sec_selector)
				->firstRow();

			if (!$this->trades->getNumRows()) {
				$this->trades
					->setWhere(P4A_DB::singleton()->getCaseInsensitiveLikeSQL('v_securities.name', "%{$value}%")." AND ".$sec_selector)
					->firstRow();
				$this->security_fund
					->setWhere(P4A_DB::singleton()->getCaseInsensitiveLikeSQL('v_securities.name', "%{$value}%")." AND ".$sec_selector)
					->firstRow();

				if (!$this->trades->getNumRows()) {
					$this->warning("No results were found");
					$this->trades
						->setWhere("trades.security_id is Not Null AND ".$sec_selector) 
						->firstRow();
					$this->security_fund
						->setWhere($sec_selector)
						->firstRow();
				}
			}
		}
	} 	

	function saveRow()
	{
		// set the internal person
		$pers_id = $this->fields->internal_person_id->getNewValue();
		if ($pers_id < 1) {
			$this->user_data
				->setWhere("username = '".$_SESSION['log_user']."'")
				->firstRow();
			$pers_id = $this->user_data->fields->internal_person_id->getValue();
			$this->fields->internal_person_id->setNewValue($pers_id);
		} 
		// set the security related values
		$sec_id = $this->fields->security_id->getNewValue();
		// set the trade currency based on the security selected if not yet set
		$curr_id = $this->fields->currency_id->getNewValue();
		if ($curr_id < 1) {
			$this->sec_data
				->setWhere("security_id = ".$sec_id)
				->firstRow();
			$curr_id = $this->sec_data->fields->currency_id->getValue();
			$this->fields->currency_id->setNewValue($curr_id);
		} 
		// set the portfolio related values
		$portfolio_id = $this->fields->portfolio_id->getNewValue();
		// set the settlement currency based on the portfolio selected if not yet set
		$curr_sett_id = $this->fields->settlement_currency_id->getNewValue();
		if ($curr_sett_id < 1) {
			$this->portfolio_data
				->setWhere("portfolio_id = ".$portfolio_id)
				->firstRow(); 
			$curr_sett_id = $this->portfolio_data->fields->currency_id->getValue();
			$this->fields->settlement_currency_id->setNewValue($curr_sett_id);
		} 
		// set the defaut trade date
		$date_trade = $this->fields->trade_date->getNewValue();
		if ($date_trade == "") {
			$date_trade = date('Y-m-d H:i:s');
			$this->fields->trade_date->setNewValue($date_trade);
		} 
		// set the defaut settlement date
		$date_settle = $this->fields->settlement_date->getNewValue();
		if ($date_settle == "") {
			$date_trade = $this->fields->trade_date->getNewValue();
			$date_settle = date('Y-m-d', strtotime(date('Y-m-d',$date_trade) . ' +3 Weekday'));
			$this->fields->settlement_date->setNewValue($date_settle);
		} 
		// set the defaut FX rate
		$fx_rate = $this->fields->fx_rate->getNewValue();
		if ($fx_rate == "" AND $curr_id > 0  AND $curr_sett_id > 0) {
			// the FX rate is always 1 if settlement currency is equal to the trade currency
			if ($curr_id == $curr_sett_id) {
				$fx_rate = 1;
			} else {
				// get the actual FX rate and set it as default
				$this->fx_data
					->setWhere("currency1_id = ".$curr_id." AND currency2_id = ".$curr_sett_id)
					->firstRow(); 
				$fx_rate = $this->fx_data->fields->fx_rate->getValue();
				$fx_decimals = $this->fx_data->fields->decimals->getValue();
				if ($fx_rate == "") {
					// get the actual FX rate and set it as default
					$this->fx_data
						->setWhere("currency1_id = ".$curr_sett_id." AND currency2_id = ".$curr_id)
						->firstRow(); 
					$fx_rate = 1/$this->fx_data->fields->fx_rate->getValue();
					$fx_decimals = $this->fx_data->fields->decimals->getValue();
					if ($fx_decimals > 0) {
						$fx_rate = round($fx_rate, $fx_decimals);
					} 
				} 
			} 
			$this->fields->fx_rate->setNewValue($fx_rate);
		} 
		// set the premium
		$premium = $this->fields->premium->getNewValue();
		if ($premium == "" AND $curr_id > 0) {
			$size = $this->fields->size->getNewValue();
			$price = $this->fields->price->getNewValue();
			if ($price <> 0 AND $size > 0) {
				$premium = $size * $price;
				$this->fields->premium->setNewValue($premium);
			} 
		} 
		// set the settlement premium
		$premium_settle = $this->fields->premium_sett->getNewValue();
		if ($premium_settle == "") {
			if ($premium <> "" AND $curr_id > 0  AND $curr_sett_id > 0) {
				if ($curr_id <> $curr_sett_id AND $fx_rate <> "") {
					$premium_settle = $premium * $fx_rate;
				} else {
					$premium_settle = $premium;
				}
			}
			$this->fields->premium_sett->setNewValue($premium_settle);
		} else {
			// set the premium based on the settlement premium
			if ($premium == "" AND $curr_id > 0  AND $curr_sett_id > 0) {
				if ($curr_id <> $curr_sett_id AND $fx_rate <> "") {
					$premium = $premium_settle / $fx_rate;
					$this->fields->premium->setNewValue($premium);
				} else {
					$premium = $premium_settle;
					$this->fields->premium->setNewValue($premium);
				}
			}
			// set the price based on the premium
			$price = $this->fields->price->getNewValue();
			if ($price == "" AND $premium <> "" AND $size > 0) {
				$price = $premium / $size;
				$this->fields->price->setNewValue($price);
			} 
		} 
		
		// set the default trade status if not set by the user
		$status_id = $this->fields->trade_status_id->getNewValue();
		if ($status_id < 1) {
			$this->status_data
				->setWhere("code_id = 'executed'")
				->firstRow();
			$status_id = $this->status_data->fields->trade_status_id->getValue();
			$this->fields->trade_status_id->setNewValue($status_id);
		} 

		// save the new value
		$this->fields->bo_status->setNewValue(1);
		//$local_log_user_mask = $p4a->menu->items->loguser->getLabel();
		parent::saveRow();
/*
		// calc the new portfolio including the trade
		$sql_sec_value = "SELECT pos.pos_value_ref FROM v_portfolio_pos pos WHERE pos.security_id = ".$this->fields->security_id->getNewValue()." AND pos.portfolio_id = ".$this->fields->portfolio_id->getNewValue().";";
		$sql_result = mysql_query($query) or die('Query failed: ' . mysql_error() . ', when executing the query ' . $query . '.');
		$sql_array = mysql_fetch_array($sql_result, MYSQL_NUM);
		if (is_null($sql_array['pos_value_ref'])) {
		  $sec_value = 0;
		} else {  
		  $sec_value = $sql_array['pos_value_ref'];
		}
		
		// reset the portfolio value
		if ($sec_value <> 0) {
		    $sql_update = "UPDATE portfolio_security_fixings SET fixed_price = '".$sec_value."' WHERE portfolio_id = ".$this->fields->portfolio_id->getNewValue()." AND security_id = ".$this->fields->security_id->getNewValue().";";
		    mysql_query($sql_update);
		} */
	} 
}
