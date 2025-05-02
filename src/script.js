document.getElementById('search').addEventListener('input', searchQuestions);
document.getElementById('category').addEventListener('change', searchQuestions);

let timeout = null;

function searchQuestions() {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        const query = document.getElementById('search').value;
        const category = document.getElementById('category').value;

        fetch(`api.php?q=${encodeURIComponent(query)}&category=${encodeURIComponent(category)}`)
            .then(res => res.json())
            .then(data => {
                const results = document.getElementById('results');
                results.innerHTML = '';

                let displayedCaseStudies = new Set();

                data.forEach(q => {
                    const div = document.createElement('div');
                    div.className = 'question';

                    let questionContent = '';

                    if (q.case_study_text && !displayedCaseStudies.has(q.case_study_text)) {
                        questionContent += `
                        <strong>Případová studie:</strong><br>    
                        <div class="case-study">
                                ${q.case_study_text}
                            </div>
                            `;
                        displayedCaseStudies.add(q.case_study_text);
                    }

                    if (q.question_text) {
                        questionContent += `<strong>Otázka:</strong> ${q.question_text}<hr>`;
                    }

                    div.innerHTML = `<strong>ID ${q.external_question_id || '-'}</strong>: ${questionContent}`;

                    const answersDiv = document.createElement('div');
                    answersDiv.className = 'answers';

                    q.answers.forEach(ans => {
                        const ansDiv = document.createElement('div');
                        ansDiv.className = 'answer' + (ans.is_correct ? ' correct' : '');
                        ansDiv.textContent = ans.answer_text;
                        answersDiv.appendChild(ansDiv);
                    });

                    div.appendChild(answersDiv);
                    results.appendChild(div);
                });
            });
    }, 300);
}



const counts = {};

document.getElementById('toggle-btn').addEventListener('click', () => {
    document.getElementById('menu-content').classList.toggle('hidden');
});

function loadExamTypes() {
    fetch('exam_types_api.php')
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('exam-type-select');
            select.innerHTML = '';

            const categories = {};
            data.forEach(exam => {
                if (!categories[exam.category]) categories[exam.category] = [];
                categories[exam.category].push(exam);
            });

            for (let category in categories) {
                const group = document.createElement('optgroup');
                group.label = category.charAt(0).toUpperCase() + category.slice(1);
                categories[category].forEach(exam => {
                    const opt = document.createElement('option');
                    opt.value = exam.id;
                    opt.textContent = exam.name;
                    group.appendChild(opt);
                });
                select.appendChild(group);
            }
            updateExamDetails();
        });
}

document.getElementById('exam-type-select').addEventListener('change', updateExamDetails);

function updateExamDetails() {
    const examId = document.getElementById('exam-type-select').value;
    fetch(`exam_details_api.php?exam_id=${examId}`)
        .then(res => res.json())
        .then(data => {
            window.currentExamData = data;
            renderExamDetails();
        });
}

function renderExamDetails() {
    const container = document.getElementById('exam-details');
    container.innerHTML = '';

    let totalLostPoints = 0;

    currentExamData.sections.forEach(section => {
        const div = document.createElement('div');
        div.className = 'exam-section';

        const requiredPercent = ((section.min_points / section.max_points) * 100).toFixed(0);

        div.innerHTML = `<h4>${section.name}: je nutné splnit na ${requiredPercent}% (${section.min_points} bodů z ${section.max_points})</h4>`;
        div.innerHTML += '<hr>';

        let sectionLostPoints = 0;

        section.questions.forEach(q => {
            const key = `${section.name}-${q.description}`;
            counts[key] = counts[key] || 0;
            sectionLostPoints += counts[key] * q.points_per_question;
        });

        const remainingPoints = section.max_points - section.min_points - sectionLostPoints;

        section.questions.forEach((q, index) => {
            const qDiv = document.createElement('div');
            qDiv.className = 'question-row';

            const key = `${section.name}-${q.description}`;
            counts[key] = counts[key] || 0;

            qDiv.innerHTML = `
                <span class="question-text">${q.description} (${q.points_per_question} bodové otázky):</span>
                <div class="controls">
                    <button class="minus" onclick="changeCount('${key}', -1)">-</button>
                    <span class="count" id="${key}">${counts[key]}</span>
                    <button class="plus" onclick="changeCount('${key}', 1)">+</button>
                    ${index === 0 ? `<small class="remaining-text">ještě lze ztratit ${remainingPoints} bodů</small>` : ''}
                </div>
            `;
            div.appendChild(qDiv);
        });

        sectionLostPoints = 0;
        section.questions.forEach(q => {
            const key = `${section.name}-${q.description}`;
            sectionLostPoints += (counts[key] || 0) * q.points_per_question;
        });

        const gainedSectionPoints = section.max_points - sectionLostPoints;
        const sectionPercent = ((gainedSectionPoints / section.max_points) * 100).toFixed(0);
        const sectionStatus = gainedSectionPoints >= section.min_points ? 'Podmínka splněna' : 'Podmínka nesplněna';
        const statusClass = gainedSectionPoints >= section.min_points ? 'section-success' : 'section-fail';

        div.innerHTML += '<hr>';
        div.innerHTML += `
            <div>Získáno bodů: ${gainedSectionPoints} (${sectionPercent}%)</div>
            <div class="${statusClass}">${sectionStatus}</div>
        `;

        container.appendChild(div);

        totalLostPoints += sectionLostPoints;
    });

    const resultDiv = document.createElement('div');
    resultDiv.className = 'exam-section';

    const currentPoints = currentExamData.total_points - totalLostPoints;
    const totalPercent = ((currentPoints / currentExamData.total_points) * 100).toFixed(0);
    const finalStatus = currentPoints >= currentExamData.min_total_points ? 'Podmínka splněna' : 'Podmínka nesplněna';
    const finalClass = currentPoints >= currentExamData.min_total_points ? 'section-success' : 'section-fail';
    const finalStatusSummary = currentPoints >= currentExamData.min_total_points ? 'ZATÍM V CAJKU' : 'PRŮSER!';
    const finalClassSummary = currentPoints >= currentExamData.min_total_points ? 'final-success' : 'final-fail';

    resultDiv.innerHTML = `
        <h4>Celkem je nutno splnit ${currentExamData.min_total_points} bodů z ${currentExamData.total_points}</h4>
        <hr>
        <div>Získáno bodů: ${currentPoints} (${totalPercent}%)</div>
        <div class="${finalClass}">${finalStatus}</div>
        <div class="${finalClassSummary}">Průběžný stav: ${finalStatusSummary}</div>
    `;

    container.appendChild(resultDiv);
}

function changeCount(key, delta) {
    counts[key] = Math.max(0, counts[key] + delta);
    document.getElementById(key).textContent = counts[key];
    calculateScore();
}

function calculateScore() {
    renderExamDetails();
}

loadExamTypes();
