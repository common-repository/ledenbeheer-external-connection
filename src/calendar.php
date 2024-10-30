<?php
function lbec_render_calendar($events_callback) {
    $currentDate = new DateTime();
    $currentDate->setTimezone(new DateTimeZone('Europe/Brussels'));
    $currentDate->setTime(0, 0, 0, 0);

    $firstDayOfMonth = clone $currentDate;
    $firstDayOfMonth->setDate($currentDate->format('Y'), $currentDate->format('m'), 1);
    $q = isset($_GET['q']) ? sanitize_key($_GET['q']) : '';
    if (strlen($q) > 1) {
        $parts = explode('_', $q);
        $firstDayOfMonth->setDate((int) $parts[0], (int) $parts[1], 1);
    }

    $previousMonth = clone $firstDayOfMonth;
    $previousMonth->modify('first day of previous month');
    $nextMonth = clone $firstDayOfMonth;
    $nextMonth->modify('first day of next month');
    $days = [
        1 => __('Monday'),
        2 => __('Tuesday'),
        3 => __('Wednesday'),
        4 => __('Thursday'),
        5 => __('Friday'),
        6 => __('Saturday'),
        7 => __('Sunday')
    ];

    $firstDay = (int) $firstDayOfMonth->format('N');
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $firstDayOfMonth->format('m'), $firstDayOfMonth->format('Y'));
    $counter = 1;
    $start = false;
    $showPrevious = true;
    $events = call_user_func($events_callback, $firstDayOfMonth);
    if ($events_callback === 'lbec_get_courses_for_date') {
        $skipDetail = get_option(PREFIX . 'skip_course_detail');
    } else {
        $skipDetail = get_option(PREFIX . 'skip_activity_detail');
    }
    $clubNid = get_option('lb_club_nid');
    ?>
        <div class="lbec-table-responsive">
            <table class="lbec-calendar">
                <thead>
                <tr>
                    <th colspan="2">
                        <?php if ($showPrevious): ?>
                            <a href="?q=<?php echo $previousMonth->format('Y_m') ?>"><?php echo __('Previous') ?></a>
                        <?php endif; ?>
                    </th>
                    <th colspan="3" style="text-align: center;"><?php echo date_i18n('Y M', (int) $firstDayOfMonth->format('U') + (int) $firstDayOfMonth->getOffset()); ?></th>
                    <th colspan="2" style="text-align: right;"><a href="?q=<?php echo $nextMonth->format('Y_m') ?>"><?php echo __('Next') ?></a></th>
                </tr>
                <tr>
                    <?php foreach ($days as $day): ?>
                        <th style="width: <?php echo (100 / 7) . '%'; ?>"><?php echo $day ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach (range(0, 5) as $m): ?>
                    <tr>
                        <?php foreach (range(1, 7) as $d) {
                            if ($d === $firstDay) {
                                $start = true;
                            }
                            $date = new DateTime();
                            $date->setTimezone(new DateTimeZone('Europe/Brussels'));
                            $date->setTime(0, 0, 0 ,0);
                            $date->setDate($firstDayOfMonth->format('Y'), $firstDayOfMonth->format('m'), $counter);
                            ?>

                            <td>
                                <?php if ($start):
                                    ?>
                                    <small class="day-number"><?php echo $date->format('d') ?></small>
                                    <?php
                                        $eventsForDate = lbec_get_events_for_date($events, $date);
                                        ?>
                                        <?php if (count($eventsForDate)): ?>
                                        <ul class="lbec-calendar-events">
                                            <?php foreach ($eventsForDate as $event):
                                                //-- Figure out link to detail, is it skipped?
                                                if ($skipDetail) {
                                                    $courseNid = get_post_meta($event->ID, 'lb_nid', true);
                                                    $permalink = lbec_get_external_link_for_course_activity($clubNid, $courseNid);
                                                } else {
                                                    $permalink = get_permalink($event->ID);
                                                }
                                            ?>
                                                <li class="lbec-calendar-event">
                                                    <?php if (!empty($event->sessions)): ?>
                                                        <?php
                                                            $sessions = lbec_filter_sessions_for_date($event->sessions, $date);
                                                        ?>
                                                        <?php foreach ($sessions as $session): ?>
                                                            <a href="<?php echo $permalink ?>"<?php echo $skipDetail ? ' target="_blank"' : ''; ?>>
                                                                <span class="lbec-calendar-event-time">
                                                                    <?php if ($session['date'] === $date->format('U') && !empty($session['from'])): ?>
                                                                        <?php echo $session['from'] ?>
                                                                    <?php else: ?>
                                                                        *
                                                                    <?php endif; ?>
                                                                </span>
                                                                <?php echo esc_html($event->post_title) ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <a href="<?php echo $permalink ?>"<?php echo $skipDetail ? ' target="_blank"' : ''; ?>>
                                                            <span class="lbec-calendar-event-time">
                                                                <?php if ((int) $date->format('U') === (int) $event->startDate): ?>
                                                                    <?php echo esc_html($event->startTime); ?>
                                                                <?php elseif (((int) $date->format('U') === (int) $event->endDate)): ?>
                                                                    <?php echo esc_html($event->endTime); ?>
                                                                <?php else: ?>
                                                                *
                                                                <?php endif; ?>
                                                            </span>
                                                            <?php echo esc_html($event->post_title) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>


                            <?php
                            if ($start) {
                                $counter++;
                                if (($counter - 1) === $daysInMonth) {
                                    break 2;
                                }
                            }
                            ?>
                        <?php } ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php
}
