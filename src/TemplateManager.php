<?php
namespace App;

use App\Context\ApplicationContext;
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

        $lesson = isset($data['lesson']) && $data['lesson'] instanceof Lesson ? $data['lesson'] : null;
        $user  = isset($data['user'])  && ($data['user']  instanceof Learner)  ? $data['user']  : $APPLICATION_CONTEXT->getCurrentUser();

        if ($lesson) {
            $lessonFromRepository = LessonRepository::getInstance()->getById($lesson->id);
            $meetingPointFromRepository = MeetingPointRepository::getInstance()->getById($lesson->meetingPointId);
            $instructorFromRepository = InstructorRepository::getInstance()->getById($lesson->instructorId);

            if($this->ShouldBeReplaced($text, '[lesson:instructor_link]')) {
                $text = str_replace('[instructor_link]',  'instructors/' . $instructorFromRepository->id .'-'.urlencode($instructorFromRepository->firstname), $text);
            } else {
                $text = str_replace('[instructor_link]', '', $text);
            }

            if ($this->ShouldBeReplaced($text, '[lesson:summary_html]')) {
                $text = str_replace('[lesson:summary_html]', Lesson::renderHtml($lessonFromRepository), $text
                );
            }
            if ($this->ShouldBeReplaced($text, '[lesson:summary]')) {
                $text = str_replace('[lesson:summary]', Lesson::renderText($lessonFromRepository), $text
                );}

            if ($this->ShouldBeReplaced($text, '[lesson:instructor_name]')) {
                $text = str_replace('[lesson:instructor_name]',$instructorFromRepository->firstname,$text);
            }

            if($this->ShouldBeReplaced($text, '[lesson:start_date]')) {
            $text = str_replace('[lesson:start_date]', $lessonFromRepository->start_time->format('d/m/Y'), $text);
            }

            if($this->ShouldBeReplaced($text, '[lesson:start_time]')) {
                $text = str_replace('[lesson:start_time]', $lessonFromRepository->start_time->format('H:i'), $text);
            }

            if($this->ShouldBeReplaced($text, '[lesson:end_time]')) {
                $text = str_replace('[lesson:end_time]', $lessonFromRepository->end_time->format('H:i'), $text);
            }

            if($meetingPointFromRepository) {
                if($this->ShouldBeReplaced($text, '[lesson:meeting_point]')) {
                    $text = str_replace('[lesson:meeting_point]', $meetingPointFromRepository->name, $text);
                }
            }
        }

        if($user) {
            if ($this->ShouldBeReplaced($text,'[user:first_name]')) {
                $text = str_replace('[user:first_name]', ucfirst(strtolower($user->firstname)), $text);
            }
        }

        return $text;
    }

    private function ShouldBeReplaced($text, $value)
    {
        return strpos($text, $value) !== false;
    }
}
