<?php
/* 

This file is part of TREAM - Portfolio Management Software.

TREAM is free software: you can redistribute it and/or modify it
under the terms of the GNU General Public License as
published by the Free Software Foundation, either version 3 of
the License, or (at your option) any later version.

TREAM is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with TREAM. If not, see <http://www.gnu.org/licenses/gpl.html>.

To contact the authors write to: 
Timon Zielonka <timon@zukunft.com>

Copyright (c) 2013-2015 zukunft.com AG, Zurich
Heang Lor <heang@zukunft.com>

http://tream.biz

 * This file is based on P4A - PHP For Applications.
 *
 * To contact the authors write to:                                     
 * Fabrizio Balliano <fabrizio@fabrizioballiano.it>                    
 * Andrea Giardina <andrea.giardina@crealabs.it>
 *
 * https://github.com/fballiano/p4a
 *
 * @author Timon Zielonka <timon@zukunft.com>
 * @copyright Copyright (c) 2013-2015 zukunft.com AG, Zurich

*/
class Security_triggers extends P4A_Base_Mask
{
	public $toolbar = null;
	public $table = null;
	public $fs_details = null;
	
	public function __construct()
	{
		parent::__construct();
		$p4a = p4a::singleton();

		$this->setSource($p4a->security_triggers);
		$this->firstRow();

		$this->fields->security_id
			->setLabel("Security")
			->setType("select")
			->setSource(P4A::singleton()->select_securities)
			->setSourceDescriptionField("select_name");

		$this->fields->trigger_type_id
			->setLabel("Type")
			->setType("select")
			->setSource(P4A::singleton()->select_security_trigger_types)
			->setSourceDescriptionField("type_name");

		$this->fields->trigger_status_id
			->setLabel("Status")
			->setType("select")
			->setSource(P4A::singleton()->select_security_trigger_stati)
			->setSourceDescriptionField("status_text");

		$this->fields->portfolio_id
			->setLabel("For Portfolio")
			->setType("select")
			->setSource(P4A::singleton()->select_portfolios)
			->setSourceDescriptionField("portfolio_select_name"); 

		$this->build("p4a_full_toolbar", "toolbar")
			->setMask($this);

		$this->build("p4a_table", "table")
			->setSource($p4a->security_triggers)
			->setVisibleCols(array("start","end","security","type","trigger_value","status"))
			->setWidth(500)
			->showNavigationBar();

		$this->build("p4a_fieldset", "fs_details")
			->setLabel("Security trigger type name")
			->anchor($this->fields->security_id)
			->anchor($this->fields->portfolio_id)
			->anchor($this->fields->trigger_type_id)
			->anchor($this->fields->trigger_value)
			->anchor($this->fields->start)
			->anchor($this->fields->end)
			->anchor($this->fields->trigger_status_id)
			->anchor($this->fields->comment);
		
		$this->frame
			->anchor($this->table)
 			->anchor($this->fs_details);

		$this
			->display("menu", $p4a->menu)
			->display("top", $this->toolbar)
			->setFocus($this->fields->trigger_type_id);
	}
}
