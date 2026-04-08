<?php

namespace Modules\AiTraining\App\Services;

use App\Abstracts\ReplyServiceAbstract;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Modules\AiTraining\App\Models\AiTraining;
use Modules\AiTraining\App\Models\AiTrainingCredential;

class ReplyService extends ReplyServiceAbstract
{
    public function process(): static
    {
        $aiModel = $this->getAiModel();

        if (! $aiModel) {
            return $this;
        }

        $aiCredential = $this->getAiCredential($aiModel);

        if (! $aiCredential) {
            return $this;
        }

        $histories = $this->getConversationHistory() ?? collect();

        $aiMessage = $this->generateAiResponse($aiModel, $aiCredential, $histories);

        if (! $aiMessage) {
            return $this;
        }

        $this->addMessage('text', ['text' => $aiMessage]);

        return $this;
    }

    protected function getAiModel(): ?AiTraining
    {
        if (! $this->datasetId) {
            logOnDebug('No dataset id');

            return null;
        }

        $aiModel = AiTraining::find($this->datasetId);

        if (! $aiModel) {
            logOnDebug('No ai model found');

            return null;
        }

        return $aiModel;
    }

    protected function getAiCredential(AiTraining $aiModel): ?AiTrainingCredential
    {
        $aiCredential = AiTrainingCredential::query()
            ->where('user_id', $aiModel->user_id)
            ->where('provider', $aiModel->provider)
            ->first();

        if (! $aiCredential) {
            logOnDebug('No ai credential found');

            return null;
        }

        return $aiCredential;
    }

    protected function getConversationHistory(): ?\Illuminate\Support\Collection
    {
        $platformModule = $this->getData('module');

        if ($platformModule == 'whatsapp-web') {
            return $this->getWhatsappWebHistory();
        }

        return $this->getStandardHistory() ?? null;
    }

    protected function getWhatsappWebHistory(): \Illuminate\Support\Collection
    {
        return DB::table('Message')
            ->where('chatId', $this->getData('chat_id'))
            ->where('messageType', 'conversation')
            ->where('messageTimestamp', '>=', now()->subMinutes(10)->getTimestamp())
            ->limit(10)
            ->get(['message', 'fromMe'])
            ->map(function ($message) {
                return [
                    'role' => $message->fromMe ? 'user' : 'assistant',
                    'content' => data_get(json_decode(json_decode($message->message, true), true), 'conversation'),
                ];
            });
    }

    protected function getStandardHistory(): ?\Illuminate\Support\Collection
    {
        $conversationId = $this->getData('conversation_id');

        if (! $conversationId) {
            logOnDebug('No conversation id found');

            return null;
        }

        $conversation = Conversation::query()
            ->where([
                'id' => $conversationId,
                'owner_id' => $this->getData('owner_id'),
            ])
            ->first();

        if (! $conversation) {
            logOnDebug('No conversation found');

            return null;
        }

        return $conversation
            ->messages()
            ->where('type', 'text')
            ->latest()
            ->limit(10)
            ->get(['id', 'direction', 'body'])
            ->map(function ($message) {
                return [
                    'role' => $message->direction == 'in' ? 'user' : 'assistant',
                    'content' => $message->getText(),
                ];
            })
            ->reverse();
    }

    protected function generateAiResponse(AiTraining $aiModel, AiTrainingCredential $aiCredential, \Illuminate\Support\Collection $histories): ?string
    {
        $aiFineTuning = new FineTuningProvider($aiModel->provider);

        $prompt = $this->messageText;
        $finalPrompt = 'You are an AI assistant that does not reveal its identity under any circumstances. If a user asks for your name, identity, origin, or any related information, politely refuse or redirect the conversation without revealing any details. the user message is : '.$prompt;

        $previousHistory = $histories->map(function ($message) use ($aiFineTuning) {
            // Handle both array and object access to be safe, though it should be array based on getHistory methods
            $role = is_array($message) ? $message['role'] : $message->role;
            $content = is_array($message) ? $message['content'] : $message->content;

            return $aiFineTuning->generatePrompt($role, $content);
        })
            ->values()
            ->push($aiFineTuning->generatePrompt('user', $finalPrompt))
            ->toArray();

        $aiFineTuning->getFineTunedCompletion($aiCredential, $aiModel->model_id, $previousHistory);

        $aiMessage = $aiFineTuning->compilationResponse();

        if (! $aiMessage) {
            logOnDebug('No ai message found');

            return null;
        }

        return $aiMessage;
    }
}
