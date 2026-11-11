var e=`<p>O consumo de água da planta subiu, mas a produção não mudou. Ninguém sabe dizer se é um vazamento, uma válvula aberta, um turno a mais ou um medidor com defeito. Enquanto a dúvida não é resolvida, a água continua sendo paga.</p>
<p>Este conteúdo é para <strong>gestores industriais, de manutenção e de facilities</strong> que querem entender o que a telemetria e a inteligência artificial realmente fazem na detecção de perdas, e quais perguntas fazer antes de contratar uma solução.</p>
<h2>Medir, alertar e analisar: três coisas diferentes</h2>
<p><img loading="lazy" src="/hidrogesp/assets/imagem-1-Cs4NE6gv.webp" alt="Fluxo ilustrativo de monitoramento hídrico em três etapas: medição, com sensores que registram vazão e pressão; análise, em que regras e IA ajudam a identificar desvios; e ação, com a equipe verificando e intervindo em campo"></p>
<p>Uma leitura mostra quanto foi consumido. Um alerta avisa que algo passou de um limite. A análise de padrões ajuda a decidir <strong>o que vale a pena investigar primeiro</strong>. Na prática, o caminho até a perda deixar de existir tem várias etapas:</p>
<ol>
<li><p><strong>Sensores</strong> medem vazão, pressão ou volume em pontos da rede.</p>
</li>
<li><p><strong>Transmissão</strong> envia essas leituras em intervalos configurados, sem depender de alguém ir até o medidor.</p>
</li>
<li><p><strong>Alertas por regras</strong> disparam quando uma condição é atingida, como consumo acima de um limite ou fluxo contínuo de madrugada.</p>
</li>
<li><p><strong>Padrões aprendidos</strong> pela IA comparam o comportamento atual com o que seria esperado para aquele horário, turno ou nível de produção.</p>
</li>
<li><p><strong>Confirmação em campo</strong> verifica se o desvio é mesmo uma perda.</p>
</li>
<li><p><strong>Reparo</strong> elimina o problema.</p>
</li>
<li><p><strong>Medição do resultado</strong> confirma que o consumo voltou ao esperado.</p>
</li>
</ol>
<p>A IA atua nas etapas 3 e 4. Sem as etapas 5 a 7, nenhum sistema reduz perda alguma. Saiba mais sobre a <a href="/hidrogesp/servicos/iot">telemetria e IoT da Hidrogesp</a>.</p>
<h2>Leitura manual, regras ou IA: qual a diferença?</h2>
<p>A comparação abaixo é conceitual, não um teste entre produtos. As capacidades reais dependem do projeto e da configuração de cada sistema.</p>
<table>
<thead>
<tr>
<th>Critério</th>
<th>Leitura periódica/manual</th>
<th>Telemetria com regras</th>
<th>Telemetria assistida por IA</th>
</tr>
</thead>
<tbody><tr>
<td>Dados</td>
<td>Registros nos momentos de coleta</td>
<td>Leituras transmitidas em intervalos configurados</td>
<td>Usa essas leituras e, quando disponíveis, variáveis de contexto</td>
</tr>
<tr>
<td>Detecção</td>
<td>Revisão humana dos registros</td>
<td>Condições como limite excedido ou uso contínuo</td>
<td>Desvios em relação a padrões estimados pelos modelos</td>
</tr>
<tr>
<td>Contexto</td>
<td>Depende de quem analisa</td>
<td>Pode usar regras por turno e horário</td>
<td>Pode incorporar relações aprendidas entre variáveis</td>
</tr>
<tr>
<td>Limitação</td>
<td>Intervalos longos entre registros</td>
<td>Regras mal ajustadas geram alertas pouco úteis</td>
<td>Depende de dados representativos e validação; também pode errar</td>
</tr>
<tr>
<td>Ação</td>
<td>Investigação e reparo pela equipe</td>
<td>O alerta precisa de responsável e atendimento</td>
<td>A indicação do modelo precisa de avaliação e resposta operacional</td>
</tr>
</tbody></table>
<p><strong>Atenção:</strong> um aumento de consumo nem sempre é vazamento. O guia da agência ambiental americana (EPA) para gestores de instalações lembra que usos contínuos legítimos também podem disparar suspeitas de vazamento. É por isso que contexto e revisão humana continuam essenciais.</p>
<h2>Um caso documentado: a rede de Singapura</h2>
<p><img loading="lazy" src="/hidrogesp/assets/imagem-2-CEQgg_iO.webp" alt="Ilustração sem escala do caso PUB em Singapura: uma estação de pressão e, a cerca de 220 metros pela tubulação, o vazamento confirmado pela equipe após a investigação orientada por IA e simulação hidráulica"></p>
<p>Em Singapura, a PUB, operadora pública de água, avaliou um sistema de detecção e localização de anomalias (ALF) em quatro redes de distribuição, com mais de <strong>1.000 km de tubulação</strong> e mais de <strong>90 sensores</strong>. O sistema combina modelos de aprendizado profundo com simulação hidráulica.</p>
<p>Em 30 e 31 de janeiro de 2024, o sistema detectou desvios de pressão em uma estação. A equipe passou a investigar trechos a até 500 metros dali e, em meados de fevereiro, confirmou um vazamento a aproximadamente <strong>220 metros da estação</strong> que havia sinalizado o problema.</p>
<p>O que esse caso mostra, e o que ele não mostra:</p>
<p>● <strong>Mostra</strong> como a análise de dados pode orientar onde a equipe deve procurar, reduzindo a área de busca.</p>
<p>● <strong>Não mostra</strong> uma previsão: as quase duas semanas são o intervalo entre o sinal e a confirmação em campo, não uma antecipação do vazamento.</p>
<p>● <strong>Não traz</strong> um percentual de economia que possa ser transferido para uma indústria brasileira.</p>
<p>● O estudo foi publicado por autores ligados à operadora e ao fornecedor da solução.</p>
<h2>Quanto custa demorar para corrigir uma perda</h2>
<p><img loading="lazy" src="/hidrogesp/assets/imagem-3-BQufuwu2.webp" alt="Simulação didática de um vazamento constante de 0,5 m³/h: reparo em 1 dia perde 12 m³, em 7 dias perde 84 m³ e em 30 dias perde 360 m³; reparar no primeiro dia evita 348 m³ de perda"></p>
<p>Uma <strong>simulação didática</strong> (não é um caso real) ajuda a entender o peso do tempo. Imagine uma perda constante de 0,5 m³/h, ou 500 litros por hora, contando todo o tempo entre o início da perda e a conclusão do reparo:</p>
<table>
<thead>
<tr>
<th>Tempo até o reparo</th>
<th>Cálculo</th>
<th align="right">Volume perdido</th>
</tr>
</thead>
<tbody><tr>
<td>1 dia</td>
<td>0,5 × 24</td>
<td align="right">12 m³</td>
</tr>
<tr>
<td>7 dias</td>
<td>0,5 × 24 × 7</td>
<td align="right">84 m³</td>
</tr>
<tr>
<td>30 dias</td>
<td>0,5 × 24 × 30</td>
<td align="right">360 m³</td>
</tr>
</tbody></table>
<p>Resolver no primeiro dia, em vez do trigésimo, representa <strong>348 m³ a menos de perda</strong> nesse cenário.</p>
<p>Para levar isso para a sua realidade, use a fórmula:</p>
<p><strong>Impacto financeiro = volume evitado × custo por m³ que realmente deixa de ser pago</strong></p>
<p>Se esse custo fosse, hipoteticamente, R$ 10/m³, a diferença seria de R$ 3.480. Esse número não é uma tarifa de concessionária. Na conta real, considere só as despesas que diminuem com a redução da perda (água, energia de bombeamento, tratamento), sem contar a mesma coisa duas vezes. Custos fixos continuam existindo.</p>
<p>A simulação mostra o valor de agir cedo. Ela não garante que qualquer sistema vá detectar e resolver o problema em um dia: sensores, conectividade, localização da perda e disponibilidade da manutenção influenciam o prazo.</p>
<h2>O que acompanhar em um piloto</h2>
<p>Ter um painel bonito não comprova economia. Estes indicadores ajudam a medir o resultado na sua própria operação:</p>
<p>● <strong>Consumo específico:</strong> m³ por tonelada produzida, ou outra unidade adequada ao seu processo. Comparar só o consumo absoluto pode esconder variações de produção.</p>
<p>● <strong>Tempo de resposta:</strong> horas entre o alerta, a confirmação e o reparo, registradas separadamente.</p>
<p>● <strong>Alertas confirmados:</strong> ocorrências confirmadas divididas pelos alertas investigados, informando também os que ainda estão pendentes.</p>
<p>● <strong>Disponibilidade dos dados:</strong> proporção de leituras válidas no período.</p>
<p>● <strong>Resultado econômico:</strong> despesas efetivamente evitadas, menos os custos de monitoramento e intervenção.</p>
<p>Para saber quanto a IA acrescenta em relação a regras simples, compare as duas abordagens na mesma operação, em períodos e condições equivalentes. Comparar casos de lugares e metodologias diferentes não serve como prova.</p>
<h2>Perguntas para fazer ao fornecedor</h2>
<p>● Quais variáveis são medidas e com que frequência os dados ficam disponíveis?</p>
<p>● Os alertas são por regras, por modelo de IA ou pelos dois? O que cada um faz?</p>
<p>● Quem recebe o alerta, em quanto tempo, e quem vai a campo confirmar?</p>
<p>● Como o sistema trata falhas de sensor e dados faltantes?</p>
<p>● Como será definida a linha de base para medir o resultado?</p>
<h2>Em resumo</h2>
<p>● Telemetria transmite leituras; regras geram alertas; a IA compara o comportamento com padrões esperados.</p>
<p>● Nenhuma dessas camadas substitui a <strong>confirmação em campo</strong> e o <strong>reparo</strong>.</p>
<p>● O caso de Singapura mostra a IA <strong>orientando a investigação</strong>, não prevendo vazamentos.</p>
<p>● O <strong>tempo até o reparo</strong> pesa muito no volume perdido.</p>
<p>● Resultado se comprova com <strong>linha de base e indicadores</strong>, não com o painel.</p>
<h2>Quer avaliar o monitoramento da sua operação?</h2>
<p>Cada planta tem uma rede, um processo e um histórico diferentes. <a href="/hidrogesp/#contato">Fale com a equipe técnica da Hidrogesp</a> para entender quais dados sua operação já tem, onde estão os pontos cegos e como montar um piloto com indicadores claros desde o início.</p>
<h3>Referências e fontes consultadas</h3>
<p>CHEW, A. W. Z. et al. A 24/7 Cloud-Hosted Solution Evaluation for Anomaly Detection and Localization of Large-Scale Water Distribution Networks in Singapore. <em>Engineering Proceedings</em>, v. 69, n. 1, p. 147, 2024. Disponível em: <a href="https://www.mdpi.com/2673-4591/69/1/147" target="_blank" rel="noopener noreferrer">mdpi.com/2673-4591/69/1/147</a>. Acesso em: 11 nov. 2026.</p>
<p>UNITED STATES ENVIRONMENTAL PROTECTION AGENCY. WaterSense. <em>Improving Water Management Using Advanced Metering Infrastructure Data: A Guide for Facility Managers</em>. Disponível em: <a href="https://nepis.epa.gov/Exe/ZyPURL.cgi?Dockey=P1015RD2.TXT" target="_blank" rel="noopener noreferrer">nepis.epa.gov</a>. Acesso em: 11 nov. 2026.</p>
<p>PUBLIC UTILITIES BOARD (Singapura). <em>Smart Water Meter</em>. Disponível em: <a href="https://www.pub.gov.sg/Public/KeyInitiatives/Smart-Water-Meter" target="_blank" rel="noopener noreferrer">pub.gov.sg</a>. Acesso em: 11 nov. 2026.</p>
`;export{e as html};