#! /bin/bash

alina_rsync_ssh_root() {
    local SOURCE="$1"
    local TARGET="$2"

    # Создаём удалённую папку
    # ssh "${ALINA_REMOTE_URL}" "sudo mkdir -p -m 755 ${TARGET}"
    # local ssh_status=$?
    # if [[ $ssh_status -ne 0 ]]; then
    #     echo "❌ Error: SSH mkdir failed (exit: $ssh_status)" >&2
    #     return $ssh_status
    # fi

    echo "🔍 Проверка изменений (dry-run)..."
    echo "📌 Источник: $SOURCE"
    echo "📌 Назначение: ${ALINA_REMOTE_URL}:${TARGET}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

    # Запускаем rsync, сравнивая ТОЛЬКО по времени изменения (mtime)
    local changes
    changes=$(rsync \
        -rltLv \
        -z \
        --dry-run \
        --itemize-changes \
        --skip-compress=jpg,jpeg,png,gif,mp4,mp3,zip,gz,pdf \
        --delete-after \
        --no-perms \
        --no-owner \
        --no-group \
        --omit-dir-times \
        --checksum \
        --filter='- **/cfg/db.php' \
        --filter='- **/cfg/mailer.php' \
        --filter='- **/uploads/' \
        --filter='P **/uploads/' \
        --filter='- **/apps/' \
        --filter='P **/apps/' \
        --filter='- database/' \
        --filter='P database/' \
        --filter='- .git/' \
        --filter='P .git/' \
        --filter='- _GITOUT/' \
        --filter='P _GITOUT/' \
        --filter='- .idea/' \
        --filter='P .idea/' \
        --filter='- .vscode/' \
        --filter='P .vscode/' \
        --filter='- nbproject/' \
        --filter='P nbproject/' \
        --filter='- **/vendor/' \
        --filter='P **/vendor/' \
        --filter='- **/letsencrypt/' \
        --filter='P **/letsencrypt/' \
        -e "ssh" \
        --rsync-path="sudo rsync" \
        "${SOURCE}" \
        "${ALINA_REMOTE_URL}:${TARGET}")

    local rsync_status=$?
    if [[ $rsync_status -ne 0 ]] && [[ $rsync_status -ne 24 ]]; then
        echo "❌ Error: rsync failed (exit: $rsync_status)" >&2
        return $rsync_status
    fi

    # Парсим вывод
    echo "$changes" | while IFS= read -r line; do
        # Пропускаем пустые строки
        [[ -z "$line" ]] && continue

        # Удаляем цвета (если есть)
        line=$(echo "$line" | sed 's/\x1b\[[0-9;]*m//g')

        # Разбираем по частям
        local prefix="${line:0:1}"
        local code="${line:1:10}"
        local filepath="${line:12}"

        # Пропускаем служебные строки
        [[ "$line" =~ ^\[ || "$line" =~ ^sending\ incremental || "$line" =~ ^recv || "$line" =~ ^total\ size ]] && continue

        # Определяем действие
        if [[ "$line" =~ ^\*deleting ]]; then
            echo "🗑️  [DELETE] $filepath"
            continue
        fi

        # Каталоги
        if [[ "$code" =~ ^d ]]; then
            if [[ "$code" == "d+++++++++" ]]; then
                echo "📁 [CREATE] $filepath"
            elif [[ "$code" =~ s|t|p|o|g ]]; then
                echo "🔧 [UPDATE] $filepath"
            fi
            continue
        fi

        # Файлы
        case "$code" in
            "f+++++++++") echo "📤 [UPLOAD] $filepath" ;;
            "f.st......"|*"c") echo "🔄 [UPDATE] $filepath" ;;
            "<f.st......"|*"<f"c*) echo "🔄 [UPDATE] $filepath" ;;
            *)
                if [[ "$prefix" == "<" ]]; then
                    echo "📤 [UPLOAD] $filepath"
                else
                    echo "📥 [DOWNLOAD] $filepath"
                fi
                ;;
        esac
    done

    # Статистика
    local upload_count=$(echo "$changes" | grep -c "^<f")
    local delete_count=$(echo "$changes" | grep -c "^\*deleting")
    local create_dir_count=$(echo "$changes" | grep -c "^cd")
    local update_count=$(echo "$changes" | grep -c "^<f.st\|^<f..c")

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "${ALINA_REMOTE_URL}:${TARGET}"
    echo "📊 Статистика (dry-run):"
    [[ $upload_count -gt 0 ]]     && echo "📤 Будет загружено: $upload_count файл(ов)"
    [[ $update_count -gt 0 ]]     && echo "🔄 Будет обновлено: $update_count файл(ов)"
    [[ $create_dir_count -gt 0 ]] && echo "📁 Будет создано: $create_dir_count папок"
    [[ $delete_count -gt 0 ]]     && echo "🗑️  Будет удалено: $delete_count файл(ов)"
    [[ $upload_count -eq 0 && $update_count -eq 0 && $delete_count -eq 0 ]] && echo "✅ Нет изменений для синхронизации"

    return $rsync_status
}

export alina_rsync_ssh_root
