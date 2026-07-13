<?php

namespace App\Filament\Resources\MessageTemplates\Schemas;

use App\Enums\MessageTemplateType;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * 对应03.3文档"保存模板时应校验变量拼写是否在允许清单内"，清单见05文档。
 */
class MessageTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('模板类型')
                    ->options(MessageTemplateType::class)
                    ->disabled(fn (string $operation) => $operation === 'edit')
                    ->required()
                    ->live(),
                TextInput::make('title')->label('标题')->required(),
                Textarea::make('content')
                    ->label('内容')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull()
                    ->helperText(fn (Get $get) => self::variableHelperText($get('type')))
                    ->rules([self::allowedVariablesRule()]),
                FileUpload::make('image_url')->label('配图')->image()->directory('message-templates'),
                TextInput::make('updated_by')->label('最后修改人')->disabled()->dehydrated(false),
            ]);
    }

    protected static function variableHelperText(?string $type): string
    {
        $type = MessageTemplateType::tryFrom($type ?? '');

        if ($type === null) {
            return '可用变量：'.implode('、', array_map(fn ($v) => "{{$v}}", MessageTemplateType::commonVariables()));
        }

        $vars = array_map(fn ($v) => "{{$v}}", $type->allowedVariables());

        return '可用变量：'.implode('、', $vars);
    }

    protected static function allowedVariablesRule(): Closure
    {
        return function (Get $get) {
            return function (string $attribute, $value, Closure $fail) use ($get) {
                $type = MessageTemplateType::tryFrom($get('type') ?? '');

                if ($type === null || ! is_string($value)) {
                    return;
                }

                preg_match_all('/\{([^{}]+)\}/u', $value, $matches);
                $used = array_unique($matches[1]);
                $invalid = array_diff($used, $type->allowedVariables());

                if (! empty($invalid)) {
                    $fail('内容包含不允许的变量：'.implode('、', array_map(fn ($v) => "{{$v}}", $invalid)));
                }
            };
        };
    }
}
