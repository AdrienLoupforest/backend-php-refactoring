<?php
namespace App;

use App\Context\ApplicationContext;
use App\Entity\Instructor;
use App\Entity\Learner;
use App\Entity\Lesson;
use App\Entity\Template;
use App\Repository\InstructorRepository;
use App\Repository\LessonRepository;
use App\Repository\MeetingPointRepository;

class TemplateManager
{
    public function getTemplateComputed(Template $tpl, array $data)
    {
        if (!$tpl) {
            throw new \RuntimeException('no tpl given');
        }

        $replaced = clone($tpl);
        $replaced->subject = $this->computeText($replaced->subject, $data);
        $replaced->content = $this->computeText($replaced->content, $data);

        return $replaced;
    }

    private function computeText($text, array $data)
    {
        $APPLICATION_CONTEXT = ApplicationContext::getInstance();

        $lesson = (isset($data['lesson']) and $data['lesson'] instanceof Lesson) ? $data['lesson'] : null;

        if ($lesson)
        {
            $lesson = LessonRepository::getInstance()->getById($lesson->id);
            $meetingPoint = MeetingPointRepository::getInstance()->getById($lesson->meetingPointId);
            $instructor = InstructorRepository::getInstance()->getById($lesson->instructorId);

            if($this->ShouldBeReplaced($text, '[lesson:instructor_link]')){
                $text = str_replace('[instructor_link]',  'instructors/' . $instructor->id .'-'.urlencode($instructor->firstname), $text);
            }

            $containsSummaryHtml = $this->ShouldBeReplaced($text, '[lesson:summary_html]');
            $containsSummary     = $this->ShouldBeReplaced($text, '[lesson:summary]');

            if ($containsSummaryHtml !== false || $containsSummary !== false) {
                if ($containsSummaryHtml !== false) {
                    $text = str_replace(
                        '[lesson:summary_html]',
                        Lesson::renderHtml($lesson),
                        $text
                    );
                }
                if ($containsSummary !== false) {
                    $text = str_replace(
                        '[lesson:summary]',
                        Lesson::renderText($lesson),
                        $text
                    );}}

            if ($this->ShouldBeReplaced($text, '[lesson:instructor_name]')) {
                $text = str_replace('[lesson:instructor_name]',$instructor->firstname,$text);
            }
        }

        if ($lesson->meetingPointId) {
            if($this->ShouldBeReplaced($text, '[lesson:meeting_point]') !== false)
                $text = str_replace('[lesson:meeting_point]', $meetingPoint->name, $text);
        }

        if($this->ShouldBeReplaced($text, '[lesson:start_date]'))
            $text = str_replace('[lesson:start_date]', $lesson->start_time->format('d/m/Y'), $text);

        if($this->ShouldBeReplaced($text, '[lesson:start_time]'))
            $text = str_replace('[lesson:start_time]', $lesson->start_time->format('H:i'), $text);

        if($this->ShouldBeReplaced($text, '[lesson:end_time]'))
            $text = str_replace('[lesson:end_time]', $lesson->end_time->format('H:i'), $text);


            if (isset($data['instructor']) && ($data['instructor']  instanceof Instructor))
                $text = str_replace('[instructor_link]',  'instructors/' . $data['instructor']->id .'-'.urlencode($data['instructor']->firstname), $text);
            else
                $text = str_replace('[instructor_link]', '', $text);

        /*
         * USER
         * [user:*]
         */
        $_user  = (isset($data['user'])  && ($data['user']  instanceof Learner))  ? $data['user']  : $APPLICATION_CONTEXT->getCurrentUser();
        if($_user) {
            if ($this->ShouldBeReplaced($text,'[user:first_name]')) {
                $text = str_replace('[user:first_name]'       , ucfirst(strtolower($_user->firstname)), $text);
            }
        }

        return $text;
    }

    private function ShouldBeReplaced($text, $value) {
        return strpos($text, $value) !== false;
    }
}
