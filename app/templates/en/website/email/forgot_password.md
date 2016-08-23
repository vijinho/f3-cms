<?php $f3 = \Base::instance();
$d = $f3->get('templateData');
$link = $d['url'] . '?code=' . $d['code'];
?>
Dear <?=$d['firstname'] ?>,

A password reset was requested from our website.

Please click on the link below to reset your password:

* [<?=$link ?>](<?=$link ?>)

Or enter the code **`<?=$d['code'] ?>`** at:

* [<?=$d['url'] ?>](<?=$d['url'] ?>)


Regards,

<?=$f3->get('email.from_name') ?>

[<?=$f3->get('email.from') ?>](mailto:<?=$f3->get('email.from') ?>)
