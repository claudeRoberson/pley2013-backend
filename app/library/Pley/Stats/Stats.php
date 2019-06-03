<?php
namespace Pley\Stats;

use \PDO;
use \DateTime;
use \DateTimeZone;
use \DateInterval;

class Stats
{
    function calc()
    {

        $config= \Pley\Config\ConfigFactory::getConfig();
        $db=$config->get('database.connections.mysql');

        $servername = $db['host'];
        $username = $db['username'];
        $password = $db['password'];
        $dbname = $db['database'];
        $port = 3306;


        $tservername = $db['host'];
        $tusername = $db['username'];
        $tpassword = $db['password'];
        $tdbname = $db['database'];
        $tport = 3306;

        /*        $tservername = $db['host'];
                $tusername = "dbtoyboxprod2";
                $tpassword = "T0yB0X11!!";
                $tdbname = "tb_bi";
                $tport = 3306;
        */

        try {
            $sourceDb = new PDO("mysql:host=$servername;dbname=$dbname;port=$port", $username, $password);
            $sourceDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $targetDb = new PDO("mysql:host=$tservername;dbname=$tdbname;port=$tport", $tusername, $tpassword);
            $targetDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }

        $metrics = [
            'new_subs',
            'churn',
            'revenue'
        ];

        $cumuls = ['new_subs', 'churn'];
        $active = ['total' => ['cumul_new_subs', 'cumul_churn']];

        $sql = "select * from subscription";
        $result = $sourceDb->query($sql);
        $subscriptionTable = [];
        foreach ($result as $r) {
            $subscriptionTable[$r['id']] = $r;
            $metrics[] = 'new_subs_' . $r['id'];
            $metrics[] = 'churn_' . $r['id'];
            $metrics[] = 'revenue_' . $r['id'];
            $cumuls[] = 'new_subs_' . $r['id'];
            $cumuls[] = 'churn_' . $r['id'];
            $active[$r['id']] = ['cumul_new_subs_' . $r['id'], 'cumul_churn_' . $r['id']];
        }

        $timeline = [];
        $startDate = new DateTime('2016-12-01', new DateTimeZone('UTC'));
        $endDate = new DateTime(null, new DateTimeZone('UTC'));
        $endDate->add(new DateInterval('P1Y'));
        while ($startDate < $endDate) {
            $d = $startDate->format('Y-m-d');
            $rec = [];
            foreach ($metrics as $v) {
                $rec[$v] = 0;
            }
            $timeline[$d] = $rec;
            $startDate->add(new DateInterval('P1D'));
        }


        $churnCohort = [];
        $startDate = new DateTime('2016-12-01', new DateTimeZone('UTC'));
        $sd = clone $startDate;
        $endDate = new DateTime(null, new DateTimeZone('UTC'));
        $endDate->add(new DateInterval('P1Y'));
        while ($startDate < $endDate) {
            $d = $startDate->format('Y-m');
            $sdd = clone $sd;
            while ($sdd < $endDate) {
                $dd = $sdd->format('Y-m');
                $churnCohort[$d][$dd] = 0;
                $sdd->add(new DateInterval('P1M'));
            }
            $startDate->add(new DateInterval('P1M'));
        }


        $sql = "SELECT *,s.created_at as created_at_profile_subscription FROM profile_subscription as s,profile_subscription_plan as p where s.id=p.profile_subscription_id";
        $result = $sourceDb->query($sql);


// Calc Daily
        foreach ($result as $r) {
            $subs_id = intval($r['subscription_id']);
            $d = (new DateTime($r['created_at'], new DateTimeZone('UTC')))->format('Y-m-d');
            $timeline[$d]['new_subs']++;
            $timeline[$d]['new_subs_' . $subs_id]++;
            if ($r['cancel_at'] != null) {
                $d = (new DateTime($r['cancel_at'], new DateTimeZone('UTC')))->format('Y-m-d');
                if (isset($timeline[$d])) {
                    $timeline[$d]['churn']++;
                    $timeline[$d]['churn_' . $subs_id]++;
                }
            }
            if ($r['gift_id'] == null) $this->calcCohort($r, $churnCohort);
        }

        ksort($timeline);

//Cumulatives
        foreach ($cumuls as $cumul) {
            $prev = 0;
            foreach ($timeline as $d => &$rec) {
                $rec['cumul_' . $cumul] = $prev + $rec[$cumul];
                $prev += $rec[$cumul];
            }
        }

//Active
        foreach ($active as $act => $parts) {
            foreach ($timeline as $d => &$rec) {
                $rec['active_' . $act] = $rec[$parts[0]] - $rec[$parts[1]];
            }
        }

        $sql = "SELECT * FROM profile_subscription_transaction as t,profile_subscription as s where t.profile_subscription_id=s.id";
        $result = $sourceDb->query($sql);
        foreach ($result as $r) {
            $d = (new DateTime($r['transaction_at'], new DateTimeZone('UTC')))->format('Y-m-d');
            $timeline[$d]['revenue'] += floatval($r['amount']);
            $timeline[$d]['revenue_' . $r['subscription_id']] += floatval($r['amount']);
        }

        $s = '';
        foreach ($churnCohort as $m1 => $m2) {
            $s .= $m1 . ',';
        }
        $s .= "\n";
        foreach ($churnCohort as $m1 => $m2) {
            foreach ($m2 as $v) {
                $s .= $v . ',';
            }
            $s .= "\n";
        }
        //file_put_contents('churn_cohort.csv', $s);


        $fields = array_keys($rec);
        $fields_for_insert = implode(',', $fields);

        foreach ($fields as &$field) {
            $field = ':' . $field;
        }
        $fields_for_insert_params = implode(',', $fields);

        $fields = array_keys($rec);

        foreach ($fields as &$field) {
            $field = $field . ' INT';
        }
        $fields_str = implode(',', $fields);


        $creTabStr = 'create table subscribers_stats (date DATE PRIMARY KEY,' . $fields_str . ')';

        $targetDb->exec('drop table IF EXISTS subscribers_stats');
        $targetDb->exec($creTabStr);
        $prep = $targetDb->prepare("INSERT INTO subscribers_stats(date, $fields_for_insert)" . " VALUES(:date, $fields_for_insert_params)");
        foreach ($timeline as $d => &$rec) {
            $rec['date'] = $d;
            $prep->execute($rec);
        }
        return ['csv'=>$s];


    }

    function calcCohort($rec, &$churnCohort)
    {
        $start = new DateTime($rec['created_at'], new DateTimeZone('UTC'));
        $start->setDate($start->format('Y'), $start->format('m'), 1);
        $start->setTime(0, 0, 0);
        $cursor = clone $start;
        if ($rec['cancel_at'] != null) {
            $end = new DateTime($rec['cancel_at'], new DateTimeZone('UTC'));
            $end->setDate($end->format('Y'), $end->format('m'), 1);
            $end->setTime(0, 0, 0);

        } else
            $end = new DateTime(null, new DateTimeZone('UTC'));

        while ($cursor < $end) {
            $churnCohort[$start->format('Y-m')][$cursor->format('Y-m')]++;
            $cursor->add(new DateInterval('P1M'));
        }

    }

}