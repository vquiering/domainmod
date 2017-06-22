<?php
/**
 * /ssl/edit.php
 *
 * This file is part of DomainMOD, an open source domain and internet asset manager.
 * Copyright (c) 2010-2017 Greg Chetcuti <greg@chetcuti.com>
 *
 * Project: http://domainmod.org   Author: http://chetcuti.com
 *
 * DomainMOD is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * DomainMOD is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with DomainMOD. If not, see
 * http://www.gnu.org/licenses/.
 *
 */
?>
<?php
require_once __DIR__ . '/../_includes/start-session.inc.php';
require_once __DIR__ . '/../_includes/init.inc.php';

require_once DIR_ROOT . '/classes/Autoloader.php';
spl_autoload_register('DomainMOD\Autoloader::classAutoloader');

$system = new DomainMOD\System();
$error = new DomainMOD\Error();
$time = new DomainMOD\Time();
$form = new DomainMOD\Form();
$timestamp = $time->stamp();
$assets = new DomainMOD\Assets();

require_once DIR_INC . '/head.inc.php';
require_once DIR_INC . '/config.inc.php';
require_once DIR_INC . '/software.inc.php';
require_once DIR_INC . '/debug.inc.php';
require_once DIR_INC . '/settings/ssl-edit.inc.php';
require_once DIR_INC . '/database.inc.php';

$pdo = $system->db();
$system->authCheck();

$del = $_GET['del'];
$really_del = $_GET['really_del'];

$sslcid = (integer) $_REQUEST['sslcid'];
$new_domain_id = (integer) $_POST['new_domain_id'];
$new_name = $_POST['new_name'];
$new_type_id = (integer) $_POST['new_type_id'];
$new_ip_id = (integer) $_POST['new_ip_id'];
$new_cat_id = (integer) $_POST['new_cat_id'];
$new_expiry_date = $_POST['new_expiry_date'];
$new_account_id = (integer) $_POST['new_account_id'];
$new_active = $_POST['new_active'];
$new_notes = $_POST['new_notes'];

// Custom Fields
$result = $pdo->query("
    SELECT field_name
    FROM ssl_cert_fields
    ORDER BY `name`")->fetchAll();

if ($result) {

    $count = 0;

    foreach ($result as $row) {

        $field_array[$count] = $row->field_name;
        $count++;

    }

    foreach ($field_array as $field) {

        $full_field = "new_" . $field . "";
        ${'new_' . $field} = $_POST[$full_field];

    }

}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $system->readOnlyCheck($_SERVER['HTTP_REFERER']);

    $date = new DomainMOD\Date();

    if ($date->checkDateFormat($new_expiry_date) && $new_name != "" && $new_domain_id != "" && $new_account_id != "" &&
        $new_type_id != "" && $new_ip_id != "" && $new_cat_id != "" && $new_domain_id != "0" && $new_account_id != "0"
        && $new_type_id != "0" && $new_ip_id != "0" && $new_cat_id != "0" && $new_active != '') {

        $stmt = $pdo->prepare("
            SELECT ssl_provider_id, owner_id
            FROM ssl_accounts
            WHERE id = :new_account_id");
        $stmt->bindValue('new_account_id', $new_account_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();

        if ($result) {

            foreach ($result as $row) {

                $new_ssl_provider_id = $result->ssl_provider_id;
                $new_owner_id = $result->owner_id;

            }

        }

        $stmt = $pdo->prepare("
            SELECT id
            FROM ssl_fees
            WHERE ssl_provider_id = :new_ssl_provider_id
              AND type_id = :new_type_id");
        $stmt->bindValue('new_ssl_provider_id', $new_ssl_provider_id, PDO::PARAM_INT);
        $stmt->bindValue('new_type_id', $new_type_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchColumn();

        if (!$result) {

            $temp_fee_id = "0";
            $temp_fee_fixed = "0";

        } else {

            $temp_fee_id = $result;
            $temp_fee_fixed = "1";

        }

        $stmt = $pdo->prepare("
            SELECT (renewal_fee + misc_fee) AS total_cost
            FROM ssl_fees
            WHERE ssl_provider_id = :new_ssl_provider_id
              AND type_id = :new_type_id");
        $stmt->bindValue('new_ssl_provider_id', $new_ssl_provider_id, PDO::PARAM_INT);
        $stmt->bindValue('new_type_id', $new_type_id, PDO::PARAM_INT);
        $stmt->execute();
        $new_total_cost = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            UPDATE ssl_certs
            SET owner_id = :new_owner_id,
                ssl_provider_id = :new_ssl_provider_id,
                account_id = :new_account_id,
                domain_id = :new_domain_id,
                `name` = :new_name,
                type_id = :new_type_id,
                ip_id = :new_ip_id,
                cat_id = :new_cat_id,
                expiry_date = :new_expiry_date,
                fee_id = :temp_fee_id,
                total_cost = :new_total_cost,
                notes = :new_notes,
                active = :new_active,
                fee_fixed = :temp_fee_fixed,
                update_time = :timestamp
            WHERE id = :sslcid");
        $stmt->bindValue('new_owner_id', $new_owner_id, PDO::PARAM_INT);
        $stmt->bindValue('new_ssl_provider_id', $new_ssl_provider_id, PDO::PARAM_INT);
        $stmt->bindValue('new_account_id', $new_account_id, PDO::PARAM_INT);
        $stmt->bindValue('new_domain_id', $new_domain_id, PDO::PARAM_INT);
        $stmt->bindValue('new_name', $new_name, PDO::PARAM_STR);
        $stmt->bindValue('new_type_id', $new_type_id, PDO::PARAM_INT);
        $stmt->bindValue('new_ip_id', $new_ip_id, PDO::PARAM_INT);
        $stmt->bindValue('new_cat_id', $new_cat_id, PDO::PARAM_INT);
        $stmt->bindValue('new_expiry_date', $new_expiry_date, PDO::PARAM_STR);
        $stmt->bindValue('temp_fee_id', $temp_fee_id, PDO::PARAM_INT);
        $stmt->bindValue('new_total_cost', strval($new_total_cost), PDO::PARAM_STR);
        $stmt->bindValue('new_notes', $new_notes, PDO::PARAM_LOB);
        $stmt->bindValue('new_active', $new_active, PDO::PARAM_INT);
        $stmt->bindValue('temp_fee_fixed', $temp_fee_fixed, PDO::PARAM_INT);
        $stmt->bindValue('timestamp', $timestamp, PDO::PARAM_STR);
        $stmt->bindValue('sslcid', $sslcid, PDO::PARAM_INT);
        $stmt->execute();

        $sql = "SELECT field_name
                FROM ssl_cert_fields
                ORDER BY `name`";
        $result = mysqli_query($dbcon, $sql);

        if (mysqli_num_rows($result) > 0) {

            $count = 0;

            while ($row = mysqli_fetch_object($result)) {

                $field_array[$count] = $row->field_name;
                $count++;

            }

            foreach ($field_array as $field) {

                $full_field = "new_" . $field;

                $sql = "UPDATE ssl_cert_field_data
                        SET `" . $field . "` = '" . mysqli_real_escape_string($dbcon, ${$full_field}) . "',
                            update_time = '" . $timestamp . "'
                        WHERE ssl_id = '" . mysqli_real_escape_string($dbcon, $sslcid) . "'";
                $result = mysqli_query($dbcon, $sql);

            }

        }

        $_SESSION['s_message_success'] .= "SSL Certificate " . $new_name . " Updated<BR>";

        $queryB = new DomainMOD\QueryBuild();

        $sql = $queryB->missingFees('ssl_certs');
        $_SESSION['s_missing_ssl_fees'] = $system->checkForRows($sql);

        header('Location: edit.php?sslcid=' . $sslcid);
        exit;

    } else {

        if ($new_name == "") {
            $_SESSION['s_message_danger'] .= "Enter the SSL certificate name<BR>";
        }
        if (!$date->checkDateFormat($new_expiry_date)) {
            $_SESSION['s_message_danger'] .= "The expiry date you entered is invalid<BR>";
        }

        if ($new_domain_id == '' || $new_domain_id == '0') {

            $_SESSION['s_message_danger'] .= "Choose the domain<BR>";

        }

        if ($new_account_id == '' || $new_account_id == '0') {

            $_SESSION['s_message_danger'] .= "Choose the SSL Provider Account<BR>";

        }

        if ($new_type_id == '' || $new_type_id == '0') {

            $_SESSION['s_message_danger'] .= "Choose the SSL Type<BR>";

        }

        if ($new_ip_id == '' || $new_ip_id == '0') {

            $_SESSION['s_message_danger'] .= "Choose the IP Address<BR>";

        }

        if ($new_cat_id == '' || $new_cat_id == '0') {

            $_SESSION['s_message_danger'] .= "Choose the Category<BR>";

        }

        if ($new_active == '') {

            $_SESSION['s_message_danger'] .= "Choose the Status<BR>";

        }

    }

} else {

    $query = "SELECT sslc.domain_id, sslc.name, sslc.expiry_date, sslc.notes, sslc.active, sslpa.id AS account_id, sslcf.id AS type_id, ip.id AS ip_id, cat.id AS cat_id
              FROM ssl_certs AS sslc, ssl_accounts AS sslpa, ssl_cert_types AS sslcf, ip_addresses AS ip, categories AS cat
              WHERE sslc.account_id = sslpa.id
                AND sslc.type_id = sslcf.id
                AND sslc.ip_id = ip.id
                AND sslc.cat_id = cat.id
                AND sslc.id = ?";
    $q = $dbcon->stmt_init();

    if ($q->prepare($query)) {

        $q->bind_param('i', $sslcid);
        $q->execute();
        $q->store_result();
        $q->bind_result($t_domain_id, $t_name, $t_expiry_date, $t_notes, $t_active, $t_account_id, $t_type_id, $t_ip_id, $t_cat_id);

        while ($q->fetch()) {

            $new_domain_id = $t_domain_id;
            $new_name = $t_name;
            $new_type_id = $t_type_id;
            $new_ip_id = $t_ip_id;
            $new_cat_id = $t_cat_id;
            $new_expiry_date = $t_expiry_date;
            $new_notes = $t_notes;
            $new_active = $t_active;
            $new_account_id = $t_account_id;

        }

        $q->close();

    } else $error->outputSqlError($dbcon, '1', 'ERROR');

}

if ($del == "1") {

    $_SESSION['s_message_danger'] .= "Are you sure you want to delete this SSL Certificate?<BR><BR>
        <a href=\"edit.php?sslcid=" . $sslcid . "&really_del=1\">YES, REALLY DELETE THIS SSL CERTIFICATE ACCOUNT</a><BR>";

}

if ($really_del == "1") {

    $stmt = $pdo->prepare("
        DELETE FROM ssl_certs
        WHERE id = :sslcid");
    $stmt->bindValue('sslcid', $sslcid, PDO::PARAM_INT);
    $stmt->execute();

    $stmt = $pdo->prepare("
        DELETE FROM ssl_cert_field_data
        WHERE ssl_id = :sslcid");
    $stmt->bindValue('sslcid', $sslcid, PDO::PARAM_INT);
    $stmt->execute();

    $temp_type = $assets->getSslType($new_type_id);

    $_SESSION['s_message_success'] .= "SSL Certificate " . $new_name . " (" . $temp_type . ") Deleted<BR>";

    $system->checkExistingAssets();

    header("Location: index.php");
    exit;

}
?>
<?php require_once DIR_INC . '/doctype.inc.php'; ?>
<html>
<head>
    <title><?php echo $system->pageTitle($page_title); ?></title>
    <?php require_once DIR_INC . '/layout/head-tags.inc.php'; ?>
</head>
<body class="hold-transition skin-red sidebar-mini">
<?php require_once DIR_INC . '/layout/header.inc.php'; ?>
<?php
echo $form->showFormTop('');
echo $form->showInputText('new_name', 'Host / Label (100)', '', $new_name, '100', '', '1', '', '');
echo $form->showInputText('new_expiry_date', 'Expiry Date (YYYY-MM-DD)', '', $new_expiry_date, '10', '', '1', '', '');

$query = "SELECT id, domain
          FROM domains
          WHERE (active NOT IN ('0', '10') OR id = ?)
          ORDER BY domain";
$q = $dbcon->stmt_init();

if ($q->prepare($query)) {

    $q->bind_param('i', $new_domain_id);
    $q->execute();
    $q->store_result();
    $q->bind_result($t_id, $t_domain);

    echo $form->showDropdownTop('new_domain_id', 'Domain', '', '1', '');

    while ($q->fetch()) {

        echo $form->showDropdownOption($t_id, $t_domain, $new_domain_id);

    }

    echo $form->showDropdownBottom('');

    $q->close();

} else $error->outputSqlError($dbcon, '1', 'ERROR');

$sql_account = "SELECT sslpa.id, sslpa.username, o.name AS o_name, sslp.name AS sslp_name
                FROM ssl_accounts AS sslpa, owners AS o, ssl_providers AS sslp
                WHERE sslpa.owner_id = o.id
                  AND sslpa.ssl_provider_id = sslp.id
                ORDER BY sslp_name ASC, o_name ASC, sslpa.username ASC";
$result_account = mysqli_query($dbcon, $sql_account) or $error->outputSqlError($dbcon, '1', 'ERROR');
echo $form->showDropdownTop('new_account_id', 'SSL Provider Account', '', '1', '');
while ($row_account = mysqli_fetch_object($result_account)) {

    echo $form->showDropdownOption($row_account->id, $row_account->sslp_name . ', ' . $row_account->o_name . ' (' . $row_account->username . ')', $new_account_id);

}
echo $form->showDropdownBottom('');

$sql_type = "SELECT id, type
             FROM ssl_cert_types
             ORDER BY type ASC";
$result_type = mysqli_query($dbcon, $sql_type) or $error->outputSqlError($dbcon, '1', 'ERROR');
echo $form->showDropdownTop('new_type_id', 'Certificate Type', '', '1', '');
while ($row_type = mysqli_fetch_object($result_type)) {

    echo $form->showDropdownOption($row_type->id, $row_type->type, $new_type_id);

}
echo $form->showDropdownBottom('');

$sql_ip = "SELECT id, ip, `name`
           FROM ip_addresses
           ORDER BY `name`, ip";
$result_ip = mysqli_query($dbcon, $sql_ip) or $error->outputSqlError($dbcon, '1', 'ERROR');
echo $form->showDropdownTop('new_ip_id', 'IP Address', '', '1', '');
while ($row_ip = mysqli_fetch_object($result_ip)) {

    echo $form->showDropdownOption($row_ip->id, $row_ip->name . ' (' . $row_ip->ip . ')', $new_ip_id);

}
echo $form->showDropdownBottom('');

$sql_cat = "SELECT id, `name`
            FROM categories
            ORDER BY `name`";
$result_cat = mysqli_query($dbcon, $sql_cat) or $error->outputSqlError($dbcon, '1', 'ERROR');
echo $form->showDropdownTop('new_cat_id', 'Category', '', '1', '');
while ($row_cat = mysqli_fetch_object($result_cat)) {

    echo $form->showDropdownOption($row_cat->id, $row_cat->name, $new_cat_id);

}
echo $form->showDropdownBottom('');

echo $form->showDropdownTop('new_active', 'Certificate Status', '', '', '');
echo $form->showDropdownOption('1', 'Active', $new_active);
echo $form->showDropdownOption('5', 'Pending (Registration)', $new_active);
echo $form->showDropdownOption('3', 'Pending (Renewal)', $new_active);
echo $form->showDropdownOption('4', 'Pending (Other)', $new_active);
echo $form->showDropdownOption('0', 'Expired', $new_active);
echo $form->showDropdownBottom('');

if ($new_notes != '') {
    $subtext = '[<a target="_blank" href="notes.php?sslcid=' . htmlentities($sslcid, ENT_QUOTES, 'UTF-8') . '">view full notes</a>]';
} else {
    $subtext = '';
}
echo $form->showInputTextarea('new_notes', 'Notes', $subtext, $new_notes, '', '', '');

$sql = "SELECT field_name
        FROM ssl_cert_fields
        ORDER BY type_id, `name`";
$result = mysqli_query($dbcon, $sql);

if (mysqli_num_rows($result) > 0) { ?>

    <h3>Custom Fields</h3><?php

    $count = 0;

    while ($row = mysqli_fetch_object($result)) {

        $field_array[$count] = $row->field_name;
        $count++;

    }

    foreach ($field_array as $field) {

        $sql = "SELECT sf.name, sf.field_name, sf.type_id, sf.description
                FROM ssl_cert_fields AS sf, custom_field_types AS cft
                WHERE sf.type_id = cft.id
                  AND sf.field_name = '" . $field . "'";
        $result = mysqli_query($dbcon, $sql);

        while ($row = mysqli_fetch_object($result)) {

            if (${'new_' . $field}) {

                $field_data = ${'new_' . $field};

            } else {

                $sql_data = "SELECT " . $row->field_name . "
                             FROM ssl_cert_field_data
                             WHERE ssl_id = '" . mysqli_real_escape_string($dbcon, $sslcid) . "'";
                $result_data = mysqli_query($dbcon, $sql_data);

                while ($row_data = mysqli_fetch_object($result_data)) {

                    $field_data = $row_data->{$row->field_name};

                }

            }

            if ($row->type_id == "1") { // Check Box

                echo $form->showCheckbox('new_' . $row->field_name, '1', $row->name, $row->description, $field_data, '', '');

            } elseif ($row->type_id == "2") { // Text

                echo $form->showInputText('new_' . $row->field_name, $row->name, $row->description, $field_data, '255', '', '', '', '');

            } elseif ($row->type_id == "3") { // Text Area

                echo $form->showInputTextarea('new_' . $row->field_name, $row->name, $row->description, $field_data, '', '', '');

            } elseif ($row->type_id == "4") { // Date

                echo $form->showInputText('new_' . $row->field_name, $row->name, $row->description, $field_data, '10', '', '', '', '');

            } elseif ($row->type_id == "5") { // Time Stamp

                echo $form->showInputText('new_' . $row->field_name, $row->name, $row->description, $field_data, '19', '', '', '', '');

            }

        }

    }

}

echo $form->showInputHidden('sslcid', $sslcid);
echo $form->showSubmitButton('Save', '', '');
echo $form->showFormBottom('');
?>
<BR><a href="edit.php?sslcid=<?php echo urlencode($sslcid); ?>&del=1">DELETE THIS SSL CERTIFICATE</a>
<?php require_once DIR_INC . '/layout/footer.inc.php'; ?>
</body>
</html>
