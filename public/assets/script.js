$(document).ready(function() {
    // textareaの高さ調整
    $(document).on("input", "textarea.autoHeight", function() {
        this.style.height = "auto";
        this.style.height = (this.scrollHeight > 50 ? this.scrollHeight : 50) + "px";
    });
    $("textarea.autoHeight").trigger("input");

    // 検索条件のアコーディオン
    $(document).on("click", "#headingSearch", function() {
        if ($("[name='accordion']").val() == 1) {
            $("[name='accordion']").val(0);
        } else {
            $("[name='accordion']").val(1);
        }
    });

    // submitボタン連打防止（POSTのみ）
    $(document).on("#submit", "form", function() {
        // formのmethodがPOSTの場合のみ処理
        if ($(this).attr("method") && $(this).attr("method").toLowerCase() === "post") {
            var submit = $(this).find("button[type='submit']");
            // ボタンが既に処理中でなければ処理を続行
            if (! submit.hasClass("processing")) {
                // 処理中クラスを追加
                submit.addClass("processing");

                // type属性を変更（submit連打してもtype="button"ならsubmitしない）
                submit.attr("type", "button");

                return true; // フォーム送信を続行
            }

            return false; // 既に処理中なら送信をキャンセル
        }

        return true; // GETは常に送信を許可
    });

    // メールテンプレートを使ったメール送信確認のポップアップ表示
    window.confirmSend = function() {
        return confirm('本当に送信しますか？');
    }

    // 削除確認のポップアップ表示
    window.confirmDelete = function() {
        return confirm('本当に削除しますか？');
    }

    // https://uxsolutions.github.io/bootstrap-datepicker/
    $(document).ready(function() {
        $(".datepicker").datepicker({
            autoclose: true,
            clearBtn: true,
            language: "ja",
            orientation: "bottom auto",
            todayHighlight: true
        });
        $(".datepicker_monthly").datepicker({
            autoclose: true,
            clearBtn: true,
            format: "yyyy/mm",
            language: "ja",
            minViewMode: "months",
            orientation: "bottom auto",
            todayHighlight: true,
            viewMode: "months"
        });
    });

    $(document).on("change", "input", function() {
        // 半角変換
        if ($(this).hasClass("castHalfWidthDigit")) {
            str = $(this).val().replace(/[\uff21-\uff3a\uff41-\uff5a\uff10-\uff19\uff20\uff3f\uff0b]/g, function(s) {
                return String.fromCharCode(s.charCodeAt(0) - 65248);
            });
            $(this).val(str);
        }
        // スペース削除
        if ($(this).hasClass("trimSpace")) {
            $(this).val($(this).val().replace(/\s+/g, ""));
        }
        // 数字のみ入力
        if ($(this).hasClass("onlyNumber")) {
            $(this).val($(this).val().replace(/\D/g, ""));
        }
        // 数字とハイフンのみ
        if ($(this).hasClass("numberWithHyphen")) {
            $(this).val($(this).val().replace(/[^0-9\-:]/g, ""));
        }
        // 英数字のみ入力
        if ($(this).hasClass("onlyNumberAlpha")) {
            $(this).val($(this).val().replace(/[^a-zA-Z0-9]/g, ""));
        }
        // ひらがなのみ入力
        if ($(this).hasClass("onlyHiragana")) {
            $(this).val($(this).val().replace(/[^\u3041-\u3093\u30fc\u3000]/g, ""));
        }
        // カタカナのみ入力
        if ($(this).hasClass("onlyKatakana")) {
            $(this).val($(this).val().replace(/[^\u30a1-\u30f6\u30fc\u3000]/g, ""));
        }
        // 半角カタカナのみ入力
        if ($(this).hasClass("onlyHalfKatakana")) {
            $(this).val($(this).val().replace(/[^\uff67-\uff9f ]/g, ""));
        }
        // メールアドレスとして有効な文字のみ
        if ($(this).hasClass("emailFmt")) {
            $(this).val($(this).val().replace(/[^a-zA-Z0-9.@_+\-]/g, ""));
        }
        // 銀行口座カナとして有効な文字のみ
        if ($(this).hasClass("bankFmt")) {
            $(this).val(bankKanaConverter.convertToBankFormat($(this).val()));
        }
        // maxlength以上の入力を削除
        if ($(this).attr("maxlength") && $(this).val().length > $(this).attr("maxlength")) {
            $(this).val($(this).val().slice(0, $(this).attr("maxlength")));
        }
    });
    $("input").trigger("change");

    // タブを閉じる
    $(document).on("click", ".closeTab", function(e) {
        e.preventDefault();
        window.close();
    });

    // 住所検索
    // リクエスト制限: 1日あたり30,000回, 1秒あたり10回までの同時リクエスト
    $(document).on("keyup", "input[name='zip[2]']", function() {
        var zip1 = $("input[name='zip[1]']").val();
        var zip2 = $(this).val();

        if (zip1.length === 3 && zip2.length === 4) {
            var api = "https://zipcloud.ibsnet.co.jp/api/search?zipcode=" + zip1 + zip2;

            $.getJSON(api)
                .done(function(data) {
                    if (data.results) {
                        var result = data.results[0];
                        $("select[name='pref_id']").val(getPrefId(result.address1));
                        $("input[name='city']").val(result.address2);
                        $("input[name='address']").val(result.address3);
                    }
                });
        }
    });

    $(document).on("keyup", "input[name='bill_zip[2]']", function() {
        var zip1 = $("input[name='bill_zip[1]']").val();
        var zip2 = $(this).val();

        if (zip1.length === 3 && zip2.length === 4) {
            var api = "https://zipcloud.ibsnet.co.jp/api/search?zipcode=" + zip1 + zip2;

            $.getJSON(api)
                .done(function(data) {
                    if (data.results) {
                        var result = data.results[0];
                        $("select[name='bill_pref_id']").val(getPrefId(result.address1));
                        $("input[name='bill_city']").val(result.address2);
                        $("input[name='bill_address']").val(result.address3);
                    }
                });
        }
    });

    $(document).on("keyup", "input[name='ship_zip[2]']", function() {
        var zip1 = $("input[name='ship_zip[1]']").val();
        var zip2 = $(this).val();

        if (zip1.length === 3 && zip2.length === 4) {
            var api = "https://zipcloud.ibsnet.co.jp/api/search?zipcode=" + zip1 + zip2;

            $.getJSON(api)
                .done(function(data) {
                    if (data.results) {
                        var result = data.results[0];
                        $("select[name='ship_pref_id']").val(getPrefId(result.address1));
                        $("input[name='ship_city']").val(result.address2);
                        $("input[name='ship_address']").val(result.address3);
                    }
                });
        }
    });

    // 都道府県名からIDを取得する
    function getPrefId(prefName) {
        var prefMap = {
            "北海道": "1",
            "青森県": "2",
            "岩手県": "3",
            "宮城県": "4",
            "秋田県": "5",
            "山形県": "6",
            "福島県": "7",
            "茨城県": "8",
            "栃木県": "9",
            "群馬県": "10",
            "埼玉県": "11",
            "千葉県": "12",
            "東京都": "13",
            "神奈川県": "14",
            "新潟県": "15",
            "富山県": "16",
            "石川県": "17",
            "福井県": "18",
            "山梨県": "19",
            "長野県": "20",
            "岐阜県": "21",
            "静岡県": "22",
            "愛知県": "23",
            "三重県": "24",
            "滋賀県": "25",
            "京都府": "26",
            "大阪府": "27",
            "兵庫県": "28",
            "奈良県": "29",
            "和歌山県": "30",
            "鳥取県": "31",
            "島根県": "32",
            "岡山県": "33",
            "広島県": "34",
            "山口県": "35",
            "徳島県": "36",
            "香川県": "37",
            "愛媛県": "38",
            "高知県": "39",
            "福岡県": "40",
            "佐賀県": "41",
            "長崎県": "42",
            "熊本県": "43",
            "大分県": "44",
            "宮崎県": "45",
            "鹿児島県": "46",
            "沖縄県": "47",
        };

        return prefMap[prefName] || '';
    }

    $(document).on("change", "#perPage", function() {
        // 現在のURLを取得
        const currentUrl = new URL(window.location.href);
        // 検索パラメーターを取得
        const searchParams = currentUrl.searchParams;
        // 検索パラメーター内にper_pageがあれば削除
        if (searchParams.has("per_page")) {
            searchParams.delete("per_page");
        }
        // プルダウンで選択した値を取得
        const selectedValue = $(this).val();
        // 検索パラメーターにプルダウンの値を追加
        searchParams.set("per_page", selectedValue);

        // 現在のドメイン+パス+パラメーターにリダイレクト
        const newUrl = `${currentUrl.origin}${currentUrl.pathname}?${searchParams.toString()}`;
        window.location.href = newUrl;
    });
});
