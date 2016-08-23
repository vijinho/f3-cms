<?php $f3 = \Base::instance();
$d = $f3->get('templateData');
$link = $d['url'] . '?code=' . $d['code'];
?>
Dear <?=$d['firstname'] ?>,

You recently registered or updated your account email address with us.

Please click on the link below to confirm your email address:

* [<?=$link ?>](<?=$link ?>)


Regards,

<?=$f3->get('email.from_name') ?>

[<?=$f3->get('email.from') ?>](mailto:<?=$f3->get('email.from') ?>)
