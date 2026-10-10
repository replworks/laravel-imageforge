# replworks/laravel-imageforge

**한 줄 요약**: wifinote.net에서 Image Forge(img.repl.net) 썸네일 URL을 모델마다 직접 조립·설정하는 불편을 없애려고, Image Forge 전용 Laravel 패키지(`replworks/laravel-imageforge`)를 만들어 Packagist에 공개하는 아이디어.

**결정 준비도**: 판단 가능 — 본인이 wifinote.net에 바로 쓰려는 패키지이고, 문제·대상·첫 사용자·비용·서비스 의존·배포 방식이 답변됐고 인터페이스 초안까지 있고 사용자가 확인함("응"). 남은 질문 없음(10번).

---

## 1. 발단

- wifinote에서 썸네일 이미지 원본을 썸네일과 미리보기에 모두 쓰고 있었음. 미리보기는 원본 그대로, 썸네일만 Image Forge(if.repl.net)를 쓰고 싶었음.
- AI가 wifinote에 직접 적용함: 썸네일은 `img.repl.net` WebP 변환 URL, 미리보기/PDF는 원본 유지, `.env`의 `IMAGE_PROXY_*`로 설정 (14개 파일 수정, 테스트 통과 — AI 작업 보고).
- 사용자: "이렇게 만드는건 너무 불편한데... 차라리 라라벨 패키지를 만드는게 좋을 것 같아."
- 불편한 점(사용자 답변): 비즈니스 로직이 아닌 코드가 대규모로 들어가고, 설정파일까지 만들어야 함.

## 2. 문제와 대상 사용자

- 문제: 위 불편에 더해, Image Forge 규격이 바뀔 때마다 귀찮은 일이 생기고 테스트를 같이 해야 함.
- 대상 사용자: Laravel을 쓰면서 Image Forge 서비스를 쓰는 사람. 외부 일반 Laravel 개발자가 아님.
- 첫 사용자: 본인. wifinote.net에 지금 써야 함.

## 3. 아이디어

사용자가 말했거나 수락한 것:

- Image Forge 전용 Laravel 패키지. 이름은 `replworks/laravel-imageforge` (웹사이트 레포가 `replworks/imageforge`이고 서비스 이름도 Image Forge).
- URL 생성을 함. 필드 방식보다 가능하면 모델에서 `->imageForge(x, y, z ...)` 형태로 호출. x는 폭, 나머지는 optional. casts 대신 모델의 명시적 메서드 방식을 채택(9번). 선택적 Trait는 함께 제공.
- 호출 형태(사용자 답변): `imageForge(100)`은 입력값(저장된 경로)을 그대로 쓰고, 뒤에 `->storage()`를 붙이면 앞에 `/storage/`가 붙음. 폭 입력이 없으면 원본 URL. `/storage/`는 Laravel 디스크 설정에서 가져옴.
- `->asset()`은 `->storage()`와 같은 방식으로 `asset()` 경로를 붙임(사용자 확인).
- 사용자: 필드 객체에서 시작하면 `$model->imageForge(100)`처럼 쓸 수 없으므로 Trait도 같이 써야 함. DB 필드 `preview_path`가 있으면 가상 메서드로 `$model->previewPathImageForge(100)` 또는 `$model->ifPreviewPath(100)`처럼 쓰고 싶음. 이름 방식은 "관습적으로 쓰는 방법을 네가 생각해봐"라며 AI에 위임.
- **채택안**(사용자가 붙여넣은 안을 "이 방식으로 가자"로 선택. 안의 제목은 "Attribute / Accessor + Builder"이지만 코드는 일반 메서드): 모델에 메서드를 직접 작성하고 `ImageForge::make($path, $width)` 빌더를 반환. DB 필드(`preview_path`)는 문자열 그대로 유지. IDE 자동완성 지원, 문자열 타입 깨짐 없음이 장점으로 제시됨.

```php
public function imageForge(?int $width = null): ImageForge
{
    return ImageForge::make($this->preview_path, $width);   // 기본 이미지 필드
}

public function previewPathImageForge(?int $width = null): ImageForge
{
    return ImageForge::make($this->preview_path, $width);   // 명시적 필드명
}
```

```php
$model->imageForge(320)->storage(); // https://img.repl.net/wifinote/storage/templates/preview.png?w=320
$model->imageForge(320)->asset();   // asset() 경로 사용
$model->imageForge()->storage();    // 폭 없이 호출하면 원본 규격 URL
```

- 옵션 전달(사용자 답변): h, fit, q, f는 빌더 체이닝이 아니라 메서드 인자로 받음. 예시: `previewPathImageForge(320, h: 240, fit: 'fill', q: 80, f: 'webp')` (초안의 인자 이름 유지).
- 기본 필드 없음(사용자 답변): 위 코드의 `imageForge()` 같은 기본 필드 메서드는 두지 않고, 필드명이 들어간 메서드(`previewPathImageForge()`)만 씀.
- Trait(사용자 답변): 기본은 모델에 명시적 메서드를 직접 쓰는 방식. 선택적 Trait를 패키지에 함께 제공.
- Trait 상세(사용자가 붙여넣은 안): 모델에 이미지 필드 목록을 배열로 선언하면 Trait가 이를 감지해 `{fieldName}ImageForge()` 형태의 호출을 동적으로 처리. 명시적 메서드 3줄을 매번 쓰지 않아도 됨.

```php
class Template extends Model
{
    use HasImageForge;

    // Trait가 매핑할 이미지 필드 목록 선언
    protected array $imageForgeFields = [
        'preview_path',
        'thumbnail_path',
    ];
}
```

```php
$template->previewPathImageForge(320, h: 240)->storage();
$template->thumbnailPathImageForge(150)->asset();
```

- Packagist에 공개해서 composer로 설치. 서비스(img-proxy)에 의존하지만 일반 공개 레포로 배포. 공개돼도 괜찮음.
- "오케이"로 동의한 AI 제안: README 첫머리에 서비스용 클라이언트임을 명시, 서비스 사용 조건 명시, base URL은 config로 분리.
- 서비스 현황: 지금은 다 무료, dogfooding 기간. Image Forge도 사용자가 만들고 운영하는 서비스.

인터페이스 초안 — **사용자가 "if.repl.net을 보고 네가 정하면 돼"라고 위임, AI가 정함, 사용자가 "응"으로 확인**:

- 서비스 규격(if.repl.net 규격표): URL은 `https://img.repl.net/{서비스 식별자}/{원본 경로}?{쿼리}`. 쿼리는 `w`(너비 0~2000, 0이면 자동), `h`(높이 0~2000, 0이면 자동), `fit`(fit/fill/auto, 그 외는 fit 처리), `q`(1~100, 기본 80, 없거나 0이면 80), `f`(avif/webp/jpeg/png, 기본은 Accept 헤더).
- 모델 메서드: `$model->imageForge(int $w, ?int $h = null, ?string $fit = null, ?int $q = null, ?string $f = null)`. x=폭만 필수, 나머지는 optional(null이면 쿼리에서 생략해 서비스 기본값에 맡김). 예: `$template->imageForge(320)`, `$template->imageForge(320, h: 240, fit: 'fill', q: 80, f: 'webp')`.
- Trait `HasImageForge`가 모델의 이미지 경로 속성을 읽음 (속성 이름은 모델에서 지정, 기본값 있음). 한 모델에 이미지가 여럿이면 named arg `source:`로 다른 속성 지정.
- Trait 없이 쓰는 URL 생성: `ImageForge::url($path, w: 320, ...)`. Trait는 이걸 호출.
- config: base URL(기본 `https://img.repl.net`), 서비스 식별자(`wifinote` 등).
- 값 검증은 서비스가 하는 쪽에 맡김(규격표에 fit·q의 잘못된 값은 서비스가 기본값 처리한다고 나와 있음).
- (채택안에는 `source:` 인자가 없음. Trait는 기본이 아니라 선택 제공(`HasImageForge`). URL 생성은 `ImageForge::make($path, $width)`로 시작.)

모델 이미지 값(사용자 답변):

- 모델에 저장된 값은 원본 경로이고 상대경로. `Storage` 함수로 URL을 만들기 때문에 앞에 `/storage/` 같은 게 붙음(Laravel 규격). 예: DB에는 `templates/modern-minimalist-tech/image.png`, 원본 URL은 `https://wifinote.test/storage/templates/modern-minimalist-tech/image.png`.
- 사용자 제안: `/storage/` 포함 여부는 Blade에서 쓰는 함수(`storage()`, `asset()` 등)에 따라 갈리니까, 이에 대응하는 메서드를 패키지에 두면 되지 않을까.

## 4. 기존 대안

- 현재 구현: 각 모델에서 직접 URL 조립 + `.env` 설정 (1번).
- 패키지 없이 하는 방법(사용자): "패키지 코드를 라라벨에 다 넣으면 되지."
- 기존 Laravel 이미지 패키지: 조사 안 함. 사용자: "이 패키지는 Image Forge 전용이잖아." 대안 비교는 "필요없어."
- 배포 방식 대안 (논의됨):
  - 비공개 GitHub 레포를 composer `vcs` repository로 등록 — "너무 불편"하다며 원치 않음.
  - Private Packagist — GitHub 연동 지원, 유료 (검색 결과: 클라우드 월 €59 + 사용자당 월 €17, 14일 무료 체험, 상시 무료 플랜 없음. 가격은 변동 가능).
  - Satis를 GitHub Pages로 배포 — "replworks 패키지를 한 곳에 묶어 운영하고 싶은 생각은 없어."
  - Gitea/GitLab 패키지 레지스트리 — 언급만 됨.
- 배경: apt 저장소는 GitHub → Pages로, homebrew 저장소도 GitHub에서 운영 중. 같은 방식으로 Packagist도 운영할 수 있는지 궁금해했음.

## 5. 증거

- **존재 증거**: 사용자 설명으로 영어권에는 이미지 프록시 서비스가 흔함(무료는 흔하지 않음). AI는 외부 서비스 의존 클라이언트 패키지가 Packagist에 흔하다고 설명함. 둘 다 근거 제시·검증은 안 됨.
- **수요 증거**: replworks 밖에서 쓰고 싶다는 요청은 없음. 사용자: "이미지 프록시 자체 수요는 꽤 있다"(근거 제시 안 됨). 또 "필요에 의해서 만드는 거라서 수요 증가는 무의미해"(사용자). 본인은 wifinote.net에 지금 필요.
- **반증**: 없음.

## 6. 반론과 의문

| 의문                                                     | 상태                                                                                                          |
| -------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| composer로 설치하려면 공개해야 하나?                     | 해결 — AI가 비공개도 가능하다고 정정, 사용자는 공개로 감                                                      |
| 서비스 의존성이 있는 패키지를 일반 레포로 배포해도 되나? | 해결 — AI: 문제없음, 사용자 "오케이"                                                                          |
| 패키지 이름                                              | 해결 — `replworks/laravel-imageforge`                                                                         |
| 서비스 규격 변경 / 서비스 중단 시 패키지는?              | 해결(사용자) — Image Forge도 본인이 만들고 운영하는 서비스라 같이 업데이트. 장애는 패키지가 신경 쓸 부분 아님 |
| `->imageForge(x, y, z ...)`의 인자                       | 해결 — 3번의 초안을 사용자가 "응"으로 확인                                                                    |
| `img.repl.net/wifinote/...` 404, 서비스명/ORIGINS 등록   | 해결 — 사용자가 Coolify 환경설정으로 직접 등록해서 풀림                                                       |
| 변환 URL 경로에 `/storage/`를 포함하나                   | 해결 — `->storage()`가 Laravel 디스크 설정에서 가져온 경로를 앞에 붙임(사용자 답변)                           |
| 외부 사용자의 서비스명/ORIGINS 등록 방법                 | 미해결 — 지금은 사용자가 직접 등록                                                                            |

## 7. 성공 조건과 한국 조건

- 한국 조건: 없음. 인프라는 Cloudflare, 서버는 Oracle Cloud 도쿄 리전.
- 비어 있는 이유(사용자 설명, 미검증): 영어권에는 흔하지만 한국은 무거운 서비스 하나로 올라가는 경우가 대부분이고 직접 만들어 쓰는 경향이 있음. 특히 AWS를 쓰고 람다로 구현해서 매니지드 서비스나 SaaS로 만들기 힘듦.
- 첫 사용자 확보, 누가 돈을 내는가, 단위 경제: 사용자 답은 수요 질문과 같은 취지 — 본인 필요로 만드는 것이라 무의미. 현재 변환 트래픽과 운영 비용은 다 무료 플랜, 서비스는 무료 dogfooding 기간.
- 실패 가능성(사용자): "내가 필요해서 만드는거라서 실패 할 수 없는 패키지."
- 치명적이면서 미검증인 항목: 대화에서 언급된 것 없음.

## 8. 검증 결과

검증 미실시.

## 9. 논의 중 바뀐 점 / 기각된 방향

- 모델별 직접 구현 → 패키지화 (사용자 제안).
- 이름: AI가 예시로 든 `laravel-image-proxy` → `replworks/laravel-imageforge`.
- Trait 지원 확정, 필드보다 `->imageForge(...)` 메서드 호출 형태를 원함. 인터페이스 설계는 AI에 위임. 이후 casts로 바꿈.
- "composer로 넣으려면 공개해야 한다"는 전제는 AI가 정정했고, 결론은 공개 유지.
- 확정(사용자): Laravel 모델 `casts`를 씀. 이미지 필드 자체가 ImageForge 객체가 됨. 호출 형태는 3번.
- 변경(사용자): casts만으로는 필드 객체가 진입점이라 `$model->imageForge(100)`처럼 쓸 수 없어서, Trait도 같이 쓰기로 함.
- 최종(사용자 선택): casts를 쓰지 않고 모델에 명시적 메서드를 직접 작성하는 방식이 기본. 선택적 Trait를 패키지가 함께 제공. DB 필드는 문자열 그대로.
- 기본 필드 메서드(`imageForge()`)는 두지 않음(사용자: "기본 필드 없어").
- 기각: vcs 방식, 자체 저장소(Satis)로 replworks 패키지를 묶어 운영하는 방향.
- Private Packagist: 채택 의사 표현 없음.
- AI가 제안했지만 수락 여부가 없는 것 (아이디어에 포함 안 함): preset(`thumbnail`/`card`/`og`), Blade directive, 테스트 assertion helper, "wifinote에서 클래스로 먼저 추출 → 별도 레포 분리 → 다른 프로젝트 적용" 순서.

## 10. 빠진 것과 미해결 질문

체크리스트 항목은 모두 한 번씩 논의됨. 인터페이스 관련 남은 질문은 없음.
