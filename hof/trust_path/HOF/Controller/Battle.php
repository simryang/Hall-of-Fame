<?php

/**
 * @author bluelovers
 * @copyright 2012
 */

class HOF_Controller_Battle extends HOF_Class_Controller
{

	/**
	 * @var HOF_Class_User
	 */
	var $user;

	protected $_cache;

	function _init()
	{
		$this->user = &HOF_Model_Main::getInstance();

		$this->options['escapeHtml'] = false;
	}

	function _main_before()
	{
		$this->_input();

		$this->user->LoadUserItem();
		$this->user->CharDataLoadAll();

		$this->_cache['lands'] = HOF_Model_Data::getLandAppear($this->user);
	}

	function _input()
	{


	}

	/**
	 * 狩場
	 */
	function _main_action_hunt()
	{
		$mapList = $this->_cache['lands'];

		$Union = array();

		if ($files = game_core::glob(UNION))
		{
			foreach ($files as $file)
			{
				$UnionMons = HOF_Model_Char::newUnionFromFile($file);
				if ($UnionMons->is_Alive()) $Union[] = $UnionMons;
			}
		}

		if ($Union)
		{

			$result = $this->user->CanUnionBattle();

			if ($result !== true)
			{
				$left_minute = floor($result / 60);
				$left_second = $result % 60;
			}

			ob_start();
			HOF_Class_Char_View::ShowCharacters($Union);
			$union_showchar = ob_get_clean();

		}

		$logs = array();

		$log = game_core::glob(LOG_BATTLE_UNION);
		foreach (array_reverse($log) as $file)
		{
			$limit++;

			$logs[] = $file;

			if (15 <= $limit) break;
		}

		$this->output->maps = $mapList;

		$this->output->union = $Union;
		$this->output->union_showchar = $union_showchar;

		$this->output->result = $result;
		$this->output->left_minute = $left_minute;
		$this->output->left_second = $left_second;

		$this->output->logs = $logs;

		$this->user->fpCloseAll();
	}

	/**
	 * 一般モンスター
	 */
	function _main_action_common()
	{
		$this->input->monster_battle = HOF::$input->post->monster_battle;
		$this->input->common = HOF::$input->request['common'];

		$this->output['battle.target.id'] = $this->input->common;
		$this->output['battle.target.from.action'] = INDEX . '?common=' . $this->output['battle.target.id'];

		if ($this->_check_land())
		{
			$land_data = HOF_Model_Data::getLandInfo($this->input->common);
			$land = $land_data['land'];

			if ($this->_cache['MonsterBattle'] = $this->MonsterBattle())
			{
				$this->user->SaveData();
			}
			else
			{
				$monster_list = $land_data['monster'];

				foreach ($monster_list as $id => $val)
				{
					if ($val[1]) $monster[] = HOF_Model_Char::newMon($id);
				}

				ob_start();

				HOF_Class_Char_View::ShowCharacters($monster, "MONSTER", $land["land"]);

				$this->output->monster_show = ob_get_clean();
			}

			$this->output->land = $land;
		}

		$this->output->monster_battle = $this->_cache['MonsterBattle'];

		$this->user->fpCloseAll();
	}

	function _main_action_simulate()
	{
		$this->input->monster_battle = HOF::$input->post->monster_battle;

		$this->output->land['name'] = '模擬戦';
		$this->output['battle.target.from.action'] = INDEX . '?simulate';
	}

	function _simulate()
	{
		if ($this->_cache['Process'] = $this->SimuBattleProcess())
		{
			$this->user->SaveData();
		}

		$this->user->fpCloseAll();
	}

	function SimuBattleProcess()
	{
		if ($this->input->monster_battle)
		{
			$this->user->MemorizeParty(); //パーティー記憶
			// 自分パーティー
			foreach ($this->user->char as $key => $val)
			{
				//チェックされたやつリスト
				if (HOF::$input->post["char_" . $key]) $MyParty[] = $this->user->char[$key];
			}
			if (count($MyParty) === 0)
			{
				$this->_error('戦闘するには最低1人必要', "margin15");
				return false;
			}
			else
			{
				if (5 < count($MyParty))
				{
					$this->_error('戦闘に出せるキャラは5人まで', "margin15");
					return false;
				}
			}
			$this->user->DoppelBattle($MyParty, 50);
			return true;
		}
	}

	function _error($s, $a = null)
	{
		$this->output->error[] = array($s, $a);
		$this->error[] = $s;
	}

	/**
	 * モンスターとの戦闘
	 */
	function MonsterBattle()
	{
		if ($this->input->monster_battle)
		{
			$this->user->MemorizeParty(); //パーティー記憶
			// そのマップで戦えるかどうか確認する。

			$land = $this->_cache['lands'];

			// Timeが足りてるかどうか確認する
			if ($this->user->time < NORMAL_BATTLE_TIME)
			{
				$this->_error("Time 不足 (必要 Time:" . NORMAL_BATTLE_TIME . ")", "margin15");
				return false;
			}

			// bluelovers
			$MyParty = array();
			// bluelovers

			// 自分パーティー
			foreach ($this->user->char as $key => $val)
			{ //チェックされたやつリスト
				if (HOF::$input->post["char_" . $key]) $MyParty[] = $this->user->char[$key];
			}

			if (count($MyParty) === 0)
			{
				$this->_error('戦闘するには最低1人必要', "margin15");
				return false;
			}
			else
			{
				if (5 < count($MyParty))
				{
					$this->_error('戦闘に出せるキャラは5人まで', "margin15");
					return false;
				}
			}

			// bluelovers
			$MyParty = HOF_Class_Battle_Team::newInstance($MyParty);
			// bluelovers

			// 敵パーティー(または一匹)

			//	include (DATA_MONSTER);
			/*
			list($Land, $MonsterList) = HOF_Model_Data::getLandInfo($this->input->common);
			*/

			$land_data = HOF_Model_Data::getLandInfo($this->input->common);

			$Land = $land_data['land'];
			$MonsterList = $land_data['monster'];

			$EneNum = $this->user->EnemyNumber($MyParty);
			$EnemyParty = $this->user->EnemyParty($EneNum, $MonsterList);

			$this->user->WasteTime(NORMAL_BATTLE_TIME); //時間の消費

			$battle = new HOF_Class_Battle($MyParty, $EnemyParty);
			$battle->SetBackGround($Land["land"]); //背景
			$battle->SetTeamName($this->user->name, $Land["name"]);
			$battle->Process(); //戦闘開始
			$battle->SaveCharacters(); //キャラデータ保存
			list($UserMoney) = $battle->ReturnMoney(); //戦闘で得た合計金額
			//お金を増やす
			$this->user->GetMoney($UserMoney);
			//戦闘ログの保存
			if ($this->user->record_btl_log) $battle->RecordLog();

			// アイテムを受け取る
			if ($itemdrop = $battle->ReturnItemGet(0))
			{
				$this->user->LoadUserItem();
				foreach ($itemdrop as $itemno => $amount) $this->user->AddItem($itemno, $amount);
				$this->user->SaveUserItem();
			}

			//dump($itemdrop);
			//dump($this->user->item);
			return true;
		}
	}

	/**
	 * まだ行けないマップなのに行こうとした。
	 */
	function _check_land()
	{
		if (!array_key_exists($this->input->common, $this->_cache['lands']))
		{
			$this->_error('マップが出現して無い (not appeared or not exist)', 'margin15');

			return false;
		}

		return true;
	}

}


?>