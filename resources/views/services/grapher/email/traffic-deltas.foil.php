<?php
    use \IXP\Services\Grapher\Graph;
?>
<html>
    <head>
        <title>Traffic Deltas Report</title>
    </head>

    <body>

        <h1>Traffic Deltas Report :: <?= $t->day->format('l') ?></h1>

        <p>
            The traffic deltas report shows member traffic profiles that have changed based on
            <?= $t->stddev ?> times the standard deviation. This report compares like for like
            traffic by only considering same day traffic. I.e. Traffic on a Tuesday for every
            Tuesday over the past x weeks.
        </p>

        <br><br>
        <?php foreach( $t->ports as $p ): ?>
            <h2>
                <?= $p['cust']->name ?>
            </h2>
            <ul>
                <?php if( $p['dIn'] > $p['thresholdIn']  ): ?>
                    <li>
                        <strong>INBOUND:</strong>

                        There has been a <strong><?= $p['percentIn'] ?>% <?= $p['sIn'] ?></strong> in this
                        member's average traffic as recorded yesterday
                        (<?= $this->grapher()->scale( $p['todayAvgIn'], Graph::CATEGORY_BITS ) ?>)
                        compared to the average over the same day for the past <?= floor( $p['days'] / 7 ) ?> weeks
                        (<?= $this->grapher()->scale( $p['meanIn'], Graph::CATEGORY_BITS ) ?>).
                        (Standard deviation: <?= $this->grapher()->scale( $p['stddevIn'], Graph::CATEGORY_BITS ) ?>).
                    </li>
                <?php endif; ?>

                <?php if( $p['dOut'] > $p['thresholdOut']  ): ?>
                    <li>
                        <strong>OUTBOUND:</strong>

                        There has been a <strong><?= $p['percentOut'] ?>% <?= $p['sOut'] ?></strong> in this
                        member's traffic as recorded yesterday
                        (<?= $this->grapher()->scale( $p['todayAvgOut'], Graph::CATEGORY_BITS ) ?>)
                        compared to the average over the same day for the past <?= floor( $p['days'] / 7 ) ?> weeks
                        (<?= $this->grapher()->scale( $p['meanOut'], Graph::CATEGORY_BITS ) ?>).
                        (Standard deviation: <?= $this->grapher()->scale( $p['stddevOut'], Graph::CATEGORY_BITS ) ?>).
                    </li>
                <?php endif; ?>
            </ul>

            <p>
                <a href="<?= route( "customer@overview" , [ 'cust' => $p['cust']->id ] ) ?>">
                    <img src="data:image/png;base64,<?= base64_encode( $p['pngMonth'] ) ?>">
                </a>
            </p>

            <p>
                <a href="<?= route( "customer@overview" , [ 'cust' => $p['cust']->id ] ) ?>">
                    <img src="data:image/png;base64,<?= base64_encode( $p['pngYear'] ) ?>">
                </a>
            </p>

            <br><hr><br>

        <?php endforeach; ?>

        <br><br>
        <p>
            <em>
                This report was generated by <a href="<?= url('') ?>">IXP Manager</a> for <?= env( 'IDENTITY_ORGNAME' ) ?>.
            </em>
        </p>
    </body>
</html>