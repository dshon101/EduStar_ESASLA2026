// ================================================================
// EDUSTAR — Shared Data & Utilities
// ================================================================

// ── AUTH HELPERS ──────────────────────────────────────────────────
function getStudent() {
  try { return JSON.parse(localStorage.getItem('edustar_current') || 'null'); } catch { return null; }
}
function saveStudent(s) {
  localStorage.setItem('edustar_current', JSON.stringify(s));
  // Also update in users list
  const users = getUsers();
  const idx = users.findIndex(u => u.email === s.email);
  if (idx !== -1) { users[idx] = s; saveUsers(users); }
}
function getUsers() { try { return JSON.parse(localStorage.getItem('edustar_users') || '[]'); } catch { return []; } }
function saveUsers(u) { localStorage.setItem('edustar_users', JSON.stringify(u)); }
function requireAuth() {
  const s = getStudent();
  if (!s) { window.location.href = '../index.html'; return null; }
  return s;
}
function logout() { localStorage.removeItem('edustar_current'); window.location.href = '../index.html'; }

// ── GRADE LEVEL MAPPING ─────────────────────────────────────────
function getGradeLevel(grade) {
  const earlyGrades = ['PP1','PP2','Grade 1','Grade 2','Grade 3','Primary 1','Primary 2','Primary 3','Standard 1','Standard 2','Standard 3','P1','P2','P3','Class 1','Class 2','Class 3','S1 (primary)','Form 1 (primary)','CI','CP','CE1'];
  const midGrades   = ['Grade 4','Grade 5','Grade 6','Grade 7','Primary 4','Primary 5','Primary 6','Standard 4','Standard 5','Standard 6','Standard 7','P4','P5','P6','P7','Class 4','Class 5','Class 6','CE2','CM1','CM2'];
  const lowerSec    = ['Grade 8','Grade 9','JSS 1','JSS 2','JSS 3','Form 1','Form 2','Form 3','S1','S2','S3','JHS 1','JHS 2','JHS 3','Standard 8','6ème','5ème','4ème','3ème'];
  const upperSec    = ['Grade 10','Grade 11','Grade 12','SSS 1','SSS 2','SSS 3','Form 4','Form 5','Form 6','S4','S5','S6','SHS 1','SHS 2','SHS 3','2nde','1ère','Terminale'];
  if (earlyGrades.some(g => grade.includes(g.replace(/\(.*\)/,'').trim()) || grade === g)) return 'early';
  if (midGrades.some(g => grade === g || grade.includes(g))) return 'middle';
  if (upperSec.some(g => grade === g || grade.includes(g))) return 'upper';
  return 'lower';
}

// ── COUNTRY NAMES ────────────────────────────────────────────────
const COUNTRY_NAMES = {
  KE:'Kenya',NG:'Nigeria',ZA:'South Africa',TZ:'Tanzania',UG:'Uganda',
  GH:'Ghana',ZW:'Zimbabwe',ZM:'Zambia',ET:'Ethiopia',RW:'Rwanda',
  MZ:'Mozambique',MW:'Malawi',BW:'Botswana',SN:'Senegal',CI:"Côte d'Ivoire"
};
const COUNTRY_FLAGS = { KE:'🇰🇪',NG:'🇳🇬',ZA:'🇿🇦',TZ:'🇹🇿',UG:'🇺🇬',GH:'🇬🇭',ZW:'🇿🇼',ZM:'🇿🇲',ET:'🇪🇹',RW:'🇷🇼',MZ:'🇲🇿',MW:'🇲🇼',BW:'🇧🇼',SN:'🇸🇳',CI:'🇨🇮' };

// ── COUNTRY-SPECIFIC CURRICULUM NOTES ───────────────────────────
const COUNTRY_CURRICULUM = {
  KE: { system:'CBC (Competency Based Curriculum)', examBoard:'KNEC', keyExams:['KCPE (Grade 6)','KCSE (Grade 12)'], languages:['English','Kiswahili'] },
  NG: { system:'9-3-4 System', examBoard:'WAEC / NECO', keyExams:['BECE (JSS3)','WASSCE (SSS3)'], languages:['English','Yoruba','Hausa','Igbo'] },
  ZA: { system:'CAPS (Curriculum and Assessment Policy Statements)', examBoard:'DBE', keyExams:['NSC (Grade 12 Matric)'], languages:['English','Afrikaans','Zulu','Xhosa','+ 8 more'] },
  TZ: { system:'Tanzania Institute of Education Curriculum', examBoard:'NECTA', keyExams:['PSLE (Standard 7)','CSEE (Form 4)','ACSEE (Form 6)'], languages:['Swahili','English'] },
  UG: { system:'Uganda National Curriculum Framework', examBoard:'UNEB', keyExams:['PLE (P7)','UCE (S4)','UACE (S6)'], languages:['English','Luganda'] },
  GH: { system:'National Curriculum Framework', examBoard:'WAEC Ghana', keyExams:['BECE (JHS3)','WASSCE (SHS3)'], languages:['English'] },
  ZW: { system:'Zimbabwe School Curriculum', examBoard:'ZIMSEC', keyExams:['Grade 7','O-Level (Form 4)','A-Level (Form 6)'], languages:['English','Shona','Ndebele'] },
  ZM: { system:'Zambia School Curriculum', examBoard:'ECZ', keyExams:['Grade 9','Grade 12'], languages:['English'] },
  ET: { system:'General Education Quality Improvement Program', examBoard:'MOE Ethiopia', keyExams:['Grade 8 National Exam','Grade 10','Grade 12 EUEE'], languages:['Amharic','English'] },
  RW: { system:'Competence Based Curriculum', examBoard:'REB', keyExams:['P6 National Exam','S3','S6 National Exam'], languages:['Kinyarwanda','English','French'] },
};

// ── SUBJECTS DATA (with grade-differentiated content) ────────────
const ALL_SUBJECTS = [
  { id:'math',    name:'Mathematics',      icon:'📐', desc:'Numbers, algebra, geometry, calculus and more', badge:{text:'Core',cls:'badge-orange'}, category:'core' },
  { id:'english', name:'English Language', icon:'📖', desc:'Grammar, writing, comprehension and literature', badge:{text:'Core',cls:'badge-orange'}, category:'core' },
  { id:'science', name:'Science',          icon:'🔬', desc:'Biology, chemistry, physics and nature', badge:{text:'Core',cls:'badge-green'}, category:'core' },
  { id:'history', name:'History',          icon:'🏛️', desc:'African and world history, culture and events', badge:{text:'Popular',cls:'badge-blue'}, category:'humanities' },
  { id:'geography', name:'Geography',      icon:'🌍', desc:'Maps, climate, ecosystems, resources', badge:{text:'Popular',cls:'badge-blue'}, category:'humanities' },
  { id:'biology', name:'Biology',          icon:'🧬', desc:'Living organisms, cells, genetics, ecology', badge:{text:'Sciences',cls:'badge-green'}, category:'sciences' },
  { id:'chemistry', name:'Chemistry',      icon:'⚗️', desc:'Atoms, molecules, reactions, periodic table', badge:{text:'Sciences',cls:'badge-green'}, category:'sciences' },
  { id:'physics',  name:'Physics',         icon:'⚛️', desc:'Motion, energy, electricity, waves', badge:{text:'Sciences',cls:'badge-green'}, category:'sciences' },
  { id:'math-adv', name:'Mathematics (Advanced)', icon:'∑', desc:'Calculus, statistics, linear algebra', badge:{text:'Advanced',cls:'badge-gold'}, category:'sciences' },
  { id:'economics', name:'Economics',      icon:'📈', desc:'Micro & macro economics, markets, trade', badge:{text:'Commerce',cls:'badge-gold'}, category:'commerce' },
  { id:'accounting', name:'Accounting',    icon:'🧾', desc:'Bookkeeping, financial statements, taxation', badge:{text:'Commerce',cls:'badge-gold'}, category:'commerce' },
  { id:'commerce', name:'Commerce',        icon:'🤝', desc:'Trade, business transactions, banking', badge:{text:'Commerce',cls:'badge-gold'}, category:'commerce' },
  { id:'business', name:'Business Studies',icon:'💼', desc:'Entrepreneurship, management, marketing', badge:{text:'Commerce',cls:'badge-gold'}, category:'commerce' },
  { id:'computer', name:'Computer Studies',icon:'💻', desc:'ICT, coding basics, digital skills, internet', badge:{text:'Tech',cls:'badge-blue'}, category:'tech' },
  { id:'literature', name:'Literature',    icon:'📚', desc:'Poetry, prose, drama, literary analysis', badge:{text:'Arts',cls:'badge-blue'}, category:'humanities' },
  { id:'religious', name:'Religious Studies',icon:'✝️', desc:'World religions, ethics, values, philosophy', badge:{text:'Humanities',cls:'badge-blue'}, category:'humanities' },
  { id:'civics',  name:'Civics / Social Studies', icon:'🗳️', desc:'Government, citizenship, human rights', badge:{text:'Humanities',cls:'badge-blue'}, category:'humanities' },
  { id:'kiswahili', name:'Kiswahili',      icon:'🗣️', desc:'Kiswahili grammar, fasihi na uandishi', badge:{text:'Language',cls:'badge-orange'}, category:'languages' },
  { id:'french',  name:'French',           icon:'🇫🇷', desc:'Grammaire française, vocabulaire et expression', badge:{text:'Language',cls:'badge-orange'}, category:'languages' },
  { id:'agriculture', name:'Agriculture',  icon:'🌱', desc:'Farming, soil science, livestock, food security', badge:{text:'Practical',cls:'badge-green'}, category:'practical' },
  { id:'art',     name:'Art & Design',     icon:'🎨', desc:'Drawing, painting, design principles', badge:{text:'Creative',cls:'badge-orange'}, category:'practical' },
  { id:'music',   name:'Music',            icon:'🎵', desc:'Music theory, instruments, African music', badge:{text:'Creative',cls:'badge-orange'}, category:'practical' },
];

// ── LESSON CONTENT BY SUBJECT & GRADE LEVEL ─────────────────────
const LESSONS = {
  math: {
    early: [
      { id:'math-e1', title:'Counting Numbers 1–100', time:'15 min', points:50, body:`<h3>Learning to Count</h3><p>Counting is the foundation of all mathematics! Let's learn to count from 1 to 100.</p><div class="highlight-box">🔢 Numbers from 1 to 10: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10<br>After 10 comes 11, 12... all the way to 20, then 30, 40... until 100!</div><h3>Counting in Tens</h3><div class="example-box">10, 20, 30, 40, 50, 60, 70, 80, 90, 100</div><p>If you have 3 groups of 10 mangoes, you have 30 mangoes altogether!</p><h3>Practice</h3><div class="highlight-box">🥚 Count the eggs in a basket: 1, 2, 3... Can you count to 20?</div>` },
      { id:'math-e2', title:'Addition & Subtraction Basics', time:'20 min', points:50, body:`<h3>Adding Numbers</h3><p>Addition means putting numbers <strong>together</strong> to find a total.</p><div class="example-box">3 + 4 = 7   (3 apples plus 4 apples = 7 apples)</div><div class="highlight-box">➕ Use your fingers to count! Hold up 3 fingers, then 4 more = 7 fingers total.</div><h3>Subtracting Numbers</h3><p>Subtraction means taking numbers <strong>away</strong>.</p><div class="example-box">10 - 3 = 7   (10 sweets minus 3 sweets = 7 sweets left)</div><h3>Word Problems</h3><p>Amara has 5 bananas. She eats 2. How many are left?</p><div class="example-box">5 - 2 = 3 bananas 🍌</div>` },
      { id:'math-e3', title:'Shapes Around Us', time:'15 min', points:50, body:`<h3>2D Shapes</h3><p>Shapes are everywhere in our world! Let's learn the most common ones.</p><div class="highlight-box">🔵 <strong>Circle</strong> — Round, like a ball or the sun<br>⬜ <strong>Square</strong> — 4 equal sides, like a window<br>🔺 <strong>Triangle</strong> — 3 sides, like a roof<br>▭ <strong>Rectangle</strong> — 4 sides, 2 long and 2 short, like a door</div><h3>Shapes in Africa</h3><p>Traditional round huts are circles. The roof is a triangle. Farms are often rectangles. Look around you — shapes are everywhere!</p>` },
    ],
    middle: [
      { id:'math-m1', title:'Understanding Fractions', time:'25 min', points:50, body:`<h3>What is a Fraction?</h3><p>A fraction represents a <strong>part of a whole</strong>. It's written as one number over another: ½, ¾, ⅔.</p><div class="highlight-box">📌 The <strong>top number</strong> = numerator (how many parts you have)<br>The <strong>bottom number</strong> = denominator (how many equal parts the whole is divided into)</div><h3>Real-Life Example</h3><div class="example-box">🥭 Cut a mango into 4 equal pieces. Eat 1 piece → you ate 1/4 of the mango.</div><h3>Adding Fractions (Same Denominator)</h3><div class="example-box">1/4 + 2/4 = 3/4</div><h3>Adding Fractions (Different Denominators)</h3><div class="example-box">1/2 + 1/3  →  3/6 + 2/6  =  5/6</div>` },
      { id:'math-m2', title:'Introduction to Algebra', time:'30 min', points:50, body:`<h3>What is Algebra?</h3><p>Algebra uses <strong>letters</strong> (variables) to represent unknown numbers.</p><div class="highlight-box">💡 A variable is like an empty box: □ + 3 = 7. What is in the box? 4!</div><h3>Solving Equations</h3><div class="example-box">x + 5 = 12\nx = 12 − 5\nx = 7 ✓</div><p>Always do the same operation on <strong>both sides</strong> — like a balanced scale!</p><h3>Why Algebra Matters</h3><p>Algebra helps engineers, programmers, doctors, and scientists solve real-world problems every day.</p>` },
      { id:'math-m3', title:'Percentages & Ratios', time:'25 min', points:50, body:`<h3>What is a Percentage?</h3><p>A percentage is a fraction out of 100. The symbol is <strong>%</strong>.</p><div class="example-box">50% = 50/100 = 0.5 = half\n75% = 75/100 = three quarters</div><div class="highlight-box">💰 If a shirt costs 500 KES and is 20% off: 20% of 500 = 100. New price = 400 KES!</div><h3>Ratios</h3><p>A ratio compares two amounts. If a class has 15 girls and 10 boys, the ratio is 15:10 or simplified 3:2.</p>` },
    ],
    lower: [
      { id:'math-l1', title:'Linear Equations & Inequalities', time:'35 min', points:50, body:`<h3>Linear Equations</h3><p>A linear equation has a variable raised to the power of 1. The graph is always a straight line.</p><div class="example-box">2x + 3 = 11\n2x = 11 - 3 = 8\nx = 8 ÷ 2 = 4</div><h3>Simultaneous Equations</h3><div class="example-box">x + y = 10  ... (i)\n2x - y = 5  ... (ii)\nAdd: 3x = 15, so x = 5, y = 5</div><div class="highlight-box">✅ Always check your answer by substituting back into BOTH equations.</div>` },
      { id:'math-l2', title:'Geometry: Angles & Triangles', time:'30 min', points:50, body:`<h3>Types of Angles</h3><div class="highlight-box">📐 Acute: less than 90° | Right: exactly 90° | Obtuse: 90°–180° | Reflex: greater than 180°</div><h3>Triangles</h3><p>All angles in a triangle add up to <strong>180°</strong>.</p><div class="example-box">Equilateral: all sides equal (60°, 60°, 60°)\nIsosceles: two sides equal\nScalene: no sides equal</div><h3>Pythagoras Theorem</h3><div class="example-box">For a right-angled triangle:\na² + b² = c²  (c = hypotenuse)</div>` },
    ],
    upper: [
      { id:'math-u1', title:'Quadratic Equations', time:'40 min', points:50, body:`<h3>Standard Form</h3><p>A quadratic equation has the form: <strong>ax² + bx + c = 0</strong></p><h3>Solving by Factorisation</h3><div class="example-box">x² + 5x + 6 = 0\n(x + 2)(x + 3) = 0\nx = -2  or  x = -3</div><h3>The Quadratic Formula</h3><div class="example-box">x = (-b ± √(b²-4ac)) / 2a</div><div class="highlight-box">💡 Use the discriminant (b²-4ac) to determine the nature of roots:<br>If > 0: two distinct real roots<br>If = 0: one repeated root<br>If < 0: no real roots (complex)</div>` },
      { id:'math-u2', title:'Calculus: Differentiation', time:'45 min', points:50, body:`<h3>What is Differentiation?</h3><p>Differentiation finds the <strong>rate of change</strong> of a function — the slope of a curve at any point.</p><h3>Basic Rules</h3><div class="example-box">d/dx(xⁿ) = nxⁿ⁻¹\nd/dx(5x³) = 15x²\nd/dx(constant) = 0</div><h3>Applications</h3><div class="highlight-box">📈 Finding maximum profit in business<br>🚗 Calculating velocity from a position function<br>📐 Optimising area and volume problems</div>` },
    ],
  },

  biology: {
    early: [{ id:'bio-e1', title:'Living and Non-Living Things', time:'20 min', points:50, body:`<h3>What is Alive?</h3><p>Living things can grow, feed, move, reproduce and respond to their environment.</p><div class="highlight-box">🌿 <strong>Living:</strong> plants, animals, fungi, bacteria<br>🪨 <strong>Non-living:</strong> rocks, water, air, soil</div><h3>Characteristics of Life (MRS GREN)</h3><div class="example-box">M - Movement\nR - Respiration\nS - Sensitivity\nG - Growth\nR - Reproduction\nE - Excretion\nN - Nutrition</div>` }],
    middle: [{ id:'bio-m1', title:'The Cell — Basic Unit of Life', time:'30 min', points:50, body:`<h3>What is a Cell?</h3><p>All living things are made of cells. A cell is the smallest unit that can perform life functions.</p><div class="highlight-box">🔬 <strong>Animal Cell:</strong> cell membrane, nucleus, cytoplasm, mitochondria<br>🌱 <strong>Plant Cell:</strong> all of the above PLUS cell wall, chloroplasts, and large vacuole</div><h3>Cell Functions</h3><div class="example-box">Nucleus → controls cell activities (the "brain")\nMitochondria → produces energy (ATP)\nChloroplasts → photosynthesis (plants only)</div>` }],
    lower: [{ id:'bio-l1', title:'Photosynthesis and Respiration', time:'35 min', points:50, body:`<h3>Photosynthesis</h3><div class="example-box">6CO₂ + 6H₂O + light energy → C₆H₁₂O₆ + 6O₂</div><p>Plants convert sunlight, water, and carbon dioxide into glucose and oxygen inside chloroplasts.</p><h3>Cellular Respiration</h3><div class="example-box">C₆H₁₂O₆ + 6O₂ → 6CO₂ + 6H₂O + ATP (energy)</div><div class="highlight-box">🌡️ Aerobic respiration uses oxygen. Anaerobic doesn't — but produces lactic acid or ethanol.</div>` }],
    upper: [{ id:'bio-u1', title:'Genetics and DNA', time:'45 min', points:50, body:`<h3>DNA Structure</h3><p>DNA (Deoxyribonucleic Acid) is a double helix made of nucleotides containing bases: <strong>A-T</strong> and <strong>G-C</strong> pairs.</p><h3>Mendelian Genetics</h3><div class="example-box">Dominant (T) vs Recessive (t)\nTT = tall, Tt = tall, tt = short\nCross Tt × Tt: TT, Tt, Tt, tt → 3:1 ratio</div><div class="highlight-box">💉 Gene mutations can cause diseases. Natural selection favours beneficial mutations over time.</div>` }],
  },

  chemistry: {
    early: [{ id:'chem-e1', title:'Matter Around Us', time:'20 min', points:50, body:`<h3>What is Matter?</h3><p>Matter is anything that has mass and takes up space. Everything around you is made of matter!</p><div class="highlight-box">💧 <strong>Solid:</strong> fixed shape and volume (rock, wood)<br>💧 <strong>Liquid:</strong> fixed volume, takes shape of container (water)<br>💨 <strong>Gas:</strong> no fixed shape or volume (air, steam)</div>` }],
    middle: [{ id:'chem-m1', title:'Atoms, Elements and Compounds', time:'30 min', points:50, body:`<h3>The Atom</h3><p>Atoms are the building blocks of matter. Everything is made of atoms!</p><div class="example-box">Protons (+) → in nucleus\nNeutrons (0) → in nucleus\nElectrons (-) → orbit nucleus</div><div class="highlight-box">🔬 An <strong>element</strong> has only one type of atom (e.g. gold = Au). A <strong>compound</strong> has two or more elements bonded together (e.g. water = H₂O).</div>` }],
    lower: [{ id:'chem-l1', title:'Chemical Reactions & Equations', time:'35 min', points:50, body:`<h3>Chemical Equations</h3><p>A chemical equation shows what happens during a reaction. Reactants → Products.</p><div class="example-box">2H₂ + O₂ → 2H₂O\n(Hydrogen + Oxygen → Water)</div><div class="highlight-box">⚖️ Law of Conservation of Mass: atoms are neither created nor destroyed — they are rearranged. Balance equations to reflect this!</div>` }],
    upper: [{ id:'chem-u1', title:'Electrochemistry & Redox Reactions', time:'45 min', points:50, body:`<h3>Oxidation and Reduction</h3><div class="highlight-box">OIL RIG:<br><strong>O</strong>xidation <strong>I</strong>s <strong>L</strong>oss of electrons<br><strong>R</strong>eduction <strong>I</strong>s <strong>G</strong>ain of electrons</div><h3>Electrochemical Cells</h3><div class="example-box">Galvanic cell → spontaneous reaction produces electricity\nElectrolytic cell → electricity forces a non-spontaneous reaction\nExample: electroplating chrome onto car parts</div>` }],
  },

  physics: {
    early: [{ id:'phy-e1', title:'Forces and Motion', time:'20 min', points:50, body:`<h3>What is a Force?</h3><p>A force is a push or pull. Forces can make objects move, stop, or change direction.</p><div class="highlight-box">🏈 Push: kicking a football away from you<br>🧲 Pull: a magnet attracting iron objects<br>🌍 Gravity: the force that pulls everything toward Earth</div>` }],
    middle: [{ id:'phy-m1', title:"Newton's Laws of Motion", time:'35 min', points:50, body:`<h3>Newton's Three Laws</h3><div class="highlight-box">1️⃣ <strong>Inertia:</strong> An object stays at rest or in motion unless a force acts on it.<br><br>2️⃣ <strong>F = ma:</strong> Force = mass × acceleration. More mass needs more force to accelerate.<br><br>3️⃣ <strong>Action-Reaction:</strong> Every action has an equal and opposite reaction.</div><h3>Real World Application</h3><div class="example-box">A car of mass 1000 kg accelerates at 3 m/s²\nForce = 1000 × 3 = 3000 N</div>` }],
    lower: [{ id:'phy-l1', title:'Electricity and Circuits', time:'35 min', points:50, body:`<h3>Electric Current</h3><p>Current is the flow of electrons through a conductor. Measured in <strong>Amperes (A)</strong>.</p><div class="example-box">Ohm's Law: V = IR\nV = Voltage (Volts), I = Current (Amps), R = Resistance (Ohms)</div><div class="highlight-box">🔌 Series circuit: all components in one loop. If one breaks, all stop.<br>⚡ Parallel circuit: multiple paths. If one breaks, others continue.</div>` }],
    upper: [{ id:'phy-u1', title:'Waves and Electromagnetic Spectrum', time:'40 min', points:50, body:`<h3>Wave Properties</h3><div class="example-box">Wavelength (λ) — distance between two peaks\nFrequency (f) — waves per second (Hz)\nAmplitude — height of wave\nSpeed: v = fλ</div><h3>The EM Spectrum</h3><div class="highlight-box">📻 Radio → Microwave → Infrared → Visible Light → UV → X-rays → Gamma rays<br><br>Higher frequency = more energy = more dangerous</div>` }],
  },

  economics: {
    lower: [{ id:'eco-l1', title:'Introduction to Economics', time:'30 min', points:50, body:`<h3>What is Economics?</h3><p>Economics studies how people, businesses, and governments make choices about scarce resources.</p><div class="highlight-box">🤔 The central economic problem: unlimited wants vs limited resources.<br>This forces us to make choices — and choices have <strong>opportunity costs</strong>.</div><h3>Key Concepts</h3><div class="example-box">Scarcity → not enough resources for all wants\nOpportunity Cost → the next best alternative given up\nProduction Possibility Frontier (PPF) → max output combinations</div>` }],
    upper: [{ id:'eco-u1', title:'Supply, Demand & Market Equilibrium', time:'40 min', points:50, body:`<h3>The Law of Demand</h3><p>As price rises, quantity demanded falls (inverse relationship) — ceteris paribus.</p><h3>The Law of Supply</h3><p>As price rises, quantity supplied rises (direct relationship).</p><h3>Market Equilibrium</h3><div class="example-box">Where supply curve meets demand curve.\nAt equilibrium: Qs = Qd\nAbove equilibrium → surplus\nBelow equilibrium → shortage</div><div class="highlight-box">🌍 In Africa, food price shocks often occur due to drought (supply shifts left) → price rises sharply.</div>` }],
  },

  accounting: {
    lower: [{ id:'acc-l1', title:'Introduction to Accounting', time:'30 min', points:50, body:`<h3>What is Accounting?</h3><p>Accounting is the process of recording, summarising, and reporting financial transactions of a business.</p><div class="highlight-box">📊 The Accounting Equation:<br><strong>Assets = Liabilities + Owner's Equity</strong></div><h3>Key Terms</h3><div class="example-box">Assets → what the business owns (cash, equipment)\nLiabilities → what the business owes (loans, creditors)\nEquity → owner's share of the business</div>` }],
    upper: [{ id:'acc-u1', title:'Financial Statements', time:'45 min', points:50, body:`<h3>The Income Statement</h3><div class="example-box">Revenue\n− Cost of Goods Sold\n= Gross Profit\n− Expenses\n= Net Profit (or Loss)</div><h3>The Balance Sheet</h3><div class="example-box">ASSETS\nCurrent Assets: cash, stock, debtors\nFixed Assets: land, machinery\n\nLIABILITIES & EQUITY\nCurrent Liabilities: creditors, bank overdraft\nLong-term Liabilities: loans\nEquity: capital + retained profit</div>` }],
  },

  english: {
    early: [{ id:'eng-e1', title:'Alphabet and Phonics', time:'15 min', points:50, body:`<h3>The English Alphabet</h3><p>There are 26 letters: <strong>A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</strong></p><div class="highlight-box">🗣️ Vowels (A, E, I, O, U) make long and short sounds.<br>Consonants make other sounds.</div><h3>Simple Words</h3><div class="example-box">cat, dog, sun, mum, run, fun, hot, pot</div><p>Sounding out each letter helps you read new words — this is called <strong>phonics</strong>!</p>` }],
    middle: [{ id:'eng-m1', title:'Parts of Speech', time:'25 min', points:50, body:`<h3>The 8 Parts of Speech</h3><div class="highlight-box">🔵 <strong>Noun</strong> — person, place, thing (Nairobi, teacher)<br>🟢 <strong>Verb</strong> — action or state (run, is, think)<br>🟡 <strong>Adjective</strong> — describes noun (clever, tall)<br>🔴 <strong>Adverb</strong> — describes verb (quickly, always)<br>🟣 <strong>Pronoun</strong> — replaces noun (he, she, they)<br>🟠 <strong>Preposition</strong> — shows position (in, on, under)<br>⚪ <strong>Conjunction</strong> — joins clauses (and, but, because)<br>🔷 <strong>Interjection</strong> — expresses emotion (Oh! Wow!)</div>` }],
    lower: [{ id:'eng-l1', title:'Essay Writing Techniques', time:'35 min', points:50, body:`<h3>Essay Structure</h3><div class="highlight-box">📝 <strong>Introduction:</strong> Hook + Background + Thesis statement<br><strong>Body Paragraphs:</strong> Topic sentence + Evidence + Analysis + Link back<br><strong>Conclusion:</strong> Restate thesis + Summarise key points + Final thought</div><h3>The PEEL Method</h3><div class="example-box">P - Point: state your argument\nE - Evidence: quote or example\nE - Explain: analyse the evidence\nL - Link: connect back to the question</div>` }],
    upper: [{ id:'eng-u1', title:'Literary Analysis & Criticism', time:'45 min', points:50, body:`<h3>Analysing Literature</h3><p>Literary analysis examines how an author uses language, structure, and form to create meaning.</p><div class="highlight-box">🔍 <strong>Theme</strong> — central idea (love, justice, identity)<br><strong>Tone</strong> — author's attitude (melancholic, ironic)<br><strong>Imagery</strong> — sensory language that creates pictures<br><strong>Symbolism</strong> — objects representing bigger ideas</div><h3>The SMILE Framework</h3><div class="example-box">S - Structure and form\nM - Mood and tone\nI - Imagery and language\nL - Language devices\nE - Effect on reader</div>` }],
  },

  commerce: {
    lower: [{ id:'com-l1', title:'Introduction to Trade', time:'25 min', points:50, body:`<h3>What is Commerce?</h3><p>Commerce covers all activities involved in the buying and selling of goods and services.</p><div class="highlight-box">🏪 <strong>Home Trade</strong> — buying and selling within a country<br>🌍 <strong>Foreign Trade</strong> — buying (import) and selling (export) between countries</div><h3>Channels of Distribution</h3><div class="example-box">Producer → Wholesaler → Retailer → Consumer\nOr: Producer → Consumer (direct sale)</div>` }],
    upper: [{ id:'com-u1', title:'Banking and Finance', time:'35 min', points:50, body:`<h3>Functions of a Bank</h3><div class="highlight-box">💰 Accept deposits → Keep money safe<br>💳 Give loans → Earn interest income<br>🔄 Transfer money → Facilitate trade<br>📊 Invest → Grow the economy</div><h3>Types of Accounts</h3><div class="example-box">Current Account → for frequent transactions, cheque book\nSavings Account → earns interest, limited withdrawals\nFixed Deposit → locked for a term, higher interest</div>` }],
  },

  business: {
    lower: [{ id:'bus-l1', title:'Introduction to Business', time:'25 min', points:50, body:`<h3>What is a Business?</h3><p>A business is an organisation that produces goods or provides services to satisfy customer needs and make a profit.</p><div class="highlight-box">🎯 <strong>Primary sector</strong> — extracting natural resources (farming, mining)<br>🏭 <strong>Secondary sector</strong> — manufacturing (factories, construction)<br>🛒 <strong>Tertiary sector</strong> — services (banking, retail, transport)</div>` }],
    upper: [{ id:'bus-u1', title:'Marketing Mix (4Ps)', time:'35 min', points:50, body:`<h3>The 4 Ps of Marketing</h3><div class="highlight-box">📦 <strong>Product</strong> — What are you selling? Features, quality, branding<br>💰 <strong>Price</strong> — Penetration, skimming, competitive pricing<br>🏪 <strong>Place</strong> — Where/how customers buy it (distribution channels)<br>📣 <strong>Promotion</strong> — Advertising, social media, word of mouth</div><h3>African Business Context</h3><div class="example-box">M-Pesa (Kenya): revolutionised mobile money\nJumia: Africa's e-commerce leader\nDangote Group: Africa's largest conglomerate</div>` }],
  },

  history: {
    early: [{ id:'hist-e1', title:'My Family and Community', time:'15 min', points:50, body:`<h3>Your Family History</h3><p>History is about the past — and it starts with YOUR family! Your parents, grandparents, and great-grandparents are part of history.</p><div class="highlight-box">👴 <strong>Grandparents</strong> — the generation before your parents<br>🏡 <strong>Community</strong> — the people who live near you<br>📖 <strong>Oral History</strong> — stories passed down through generations by talking, not writing</div>` }],
    middle: [{ id:'hist-m1', title:'Ancient African Kingdoms', time:'30 min', points:50, body:`<h3>Great African Kingdoms</h3><div class="highlight-box">🏛️ <strong>Ancient Egypt</strong> — One of the world's first civilisations. Built pyramids, developed writing (hieroglyphics), advanced medicine.<br><br>🌍 <strong>Kingdom of Mali</strong> — Mansa Musa was possibly the wealthiest person in history. Timbuktu was a centre of Islamic scholarship.<br><br>⛏️ <strong>Kingdom of Zimbabwe</strong> — Built the Great Zimbabwe stone structures, a major trading power 900–1400 CE.</div>` }],
    lower: [{ id:'hist-l1', title:'African Independence Movements', time:'35 min', points:50, body:`<h3>Colonialism in Africa</h3><p>By the early 1900s, European powers controlled most of Africa, exploiting resources and people.</p><div class="highlight-box">🇬🇭 <strong>Ghana 1957</strong> — Kwame Nkrumah led first sub-Saharan independence<br>🇰🇪 <strong>Kenya 1963</strong> — Jomo Kenyatta became first PM after Mau Mau uprising<br>🇿🇦 <strong>South Africa 1994</strong> — Apartheid ended; Nelson Mandela elected president<br>🇿🇲 <strong>Zambia 1964</strong> — Kenneth Kaunda led independence from Britain</div>` }],
    upper: [{ id:'hist-u1', title:'Cold War and Africa', time:'40 min', points:50, body:`<h3>Cold War Impact on Africa</h3><p>The Cold War (1947–1991) was a geopolitical struggle between the USA and USSR. Africa became a battleground for influence.</p><div class="highlight-box">🇦🇴 <strong>Angola Civil War</strong> — Soviet/Cuba backed MPLA vs US-backed UNITA<br>🇨🇩 <strong>Congo Crisis</strong> — USA backed the overthrow of Patrice Lumumba (1961)<br>🇸🇴 <strong>Ethiopia/Somalia</strong> — Superpowers switched sides during the Ogaden War</div>` }],
  },

  geography: {
    early: [{ id:'geo-e1', title:'My Local Area', time:'15 min', points:50, body:`<h3>Learning About Where You Live</h3><p>Geography starts with understanding your own neighbourhood and country!</p><div class="highlight-box">🏠 <strong>Village/Town</strong> — where you live<br>🗺️ <strong>Map</strong> — a drawing that shows places from above<br>🧭 <strong>Compass</strong> — helps us find directions: North, South, East, West</div>` }],
    middle: [{ id:'geo-m1', title:"Africa's Landforms and Biomes", time:'30 min', points:50, body:`<h3>Major Landforms</h3><div class="highlight-box">🏜️ <strong>Sahara Desert</strong> — world's largest hot desert<br>🌿 <strong>Congo Rainforest</strong> — 2nd largest rainforest<br>🏔️ <strong>Mount Kilimanjaro</strong> — 5,895m, Tanzania<br>🌊 <strong>Nile River</strong> — 6,650km, world's longest river<br>🦒 <strong>Serengeti</strong> — world's largest wildlife migration</div>` }],
    lower: [{ id:'geo-l1', title:'Population and Urbanisation in Africa', time:'35 min', points:50, body:`<h3>Population Growth</h3><p>Africa has the world's fastest-growing population. By 2050, Africa will have over 2 billion people.</p><div class="highlight-box">📈 <strong>Urbanisation</strong> — movement of people from rural areas to cities<br>🏙️ <strong>Mega-cities:</strong> Lagos (25M+), Cairo (21M+), Kinshasa (16M+), Nairobi (5M+)</div><h3>Challenges of Rapid Urbanisation</h3><div class="example-box">+ Economic opportunities, better services\n- Informal settlements (slums), traffic, pollution, unemployment</div>` }],
    upper: [{ id:'geo-u1', title:'Climate Change and Africa', time:'40 min', points:50, body:`<h3>Africa's Climate Vulnerability</h3><p>Despite contributing less than 4% of global emissions, Africa faces the most severe impacts of climate change.</p><div class="highlight-box">🌡️ Rising temperatures → more intense droughts<br>🌊 Rising sea levels → flooding in coastal cities<br>🌧️ Erratic rainfall → failed harvests, food insecurity<br>🦟 Expanded malaria zones → health crisis</div><h3>African Solutions</h3><div class="example-box">Great Green Wall — planting trees across the Sahel\nM-Kopa Solar — solar power for off-grid homes in Kenya</div>` }],
  },

  literature: {
    lower: [{ id:'lit-l1', title:'Introduction to Poetry', time:'30 min', points:50, body:`<h3>What is Poetry?</h3><p>Poetry uses language in a concentrated, musical way to express feelings, ideas, and experiences.</p><div class="highlight-box">🎵 <strong>Rhythm</strong> — the beat of a poem<br>🔤 <strong>Rhyme</strong> — words that sound alike (moon/June)<br>📸 <strong>Imagery</strong> — words that create mental pictures<br>🔄 <strong>Repetition</strong> — words repeated for emphasis</div><h3>African Poetry Tradition</h3><p>Africa has a rich oral poetry tradition. Praise poetry (like Zulu <em>izibongo</em>) celebrates ancestors, kings, and community heroes.</p>` }],
    upper: [{ id:'lit-u1', title:'African Literature: Chinua Achebe', time:'45 min', points:50, body:`<h3>Chinua Achebe (1930–2013)</h3><p>Nigerian author Chinua Achebe is considered the "father of African literature." His novel <em>Things Fall Apart</em> (1958) was the first widely read African novel in English.</p><div class="highlight-box">📖 <strong>Things Fall Apart</strong> — follows Okonkwo, an Igbo warrior whose world is destroyed by colonial Christianity and British rule<br><strong>Theme:</strong> clash of cultures, colonialism, masculinity, tradition vs change<br><strong>Narrative style:</strong> uses Igbo proverbs and folklore</div>` }],
  },

  religious: {
    middle: [{ id:'rel-m1', title:'World Religions Overview', time:'25 min', points:50, body:`<h3>Major World Religions</h3><div class="highlight-box">☪️ <strong>Islam</strong> — 1.9 billion followers. 5 pillars: Shahada, Salat, Zakat, Sawm, Hajj. Sacred text: Quran.<br><br>✝️ <strong>Christianity</strong> — 2.4 billion followers. Based on teachings of Jesus Christ. Sacred text: Bible.<br><br>🕉️ <strong>Hinduism</strong> — 1.2 billion followers. Multiple deities, karma, reincarnation.<br><br>✡️ <strong>Judaism</strong> — 15 million followers. Monotheistic, Torah, covenant with God.</div>` }],
    upper: [{ id:'rel-u1', title:'Ethics and Moral Philosophy', time:'35 min', points:50, body:`<h3>Ethical Frameworks</h3><div class="highlight-box">⚖️ <strong>Utilitarianism</strong> (Bentham, Mill) — the right action is the one that produces the greatest good for the greatest number<br><br>📋 <strong>Kantian Ethics</strong> (Kant) — act only according to rules you'd want to be universal laws<br><br>🌱 <strong>Ubuntu Philosophy</strong> (African) — "I am because we are" — community and relationship-centred ethics</div>` }],
  },

  computer: {
    early: [{ id:'comp-e1', title:'What is a Computer?', time:'15 min', points:50, body:`<h3>Computer Basics</h3><p>A computer is a machine that can store and process information. It follows instructions given by programs.</p><div class="highlight-box">🖥️ <strong>Monitor</strong> — the screen you look at<br>⌨️ <strong>Keyboard</strong> — to type letters and numbers<br>🖱️ <strong>Mouse</strong> — to point and click<br>💾 <strong>CPU</strong> — the brain of the computer</div>` }],
    middle: [{ id:'comp-m1', title:'Introduction to the Internet', time:'25 min', points:50, body:`<h3>What is the Internet?</h3><p>The internet is a global network of connected computers that share information.</p><div class="highlight-box">🌐 <strong>Website</strong> — pages of information (like Google, Wikipedia)<br>📧 <strong>Email</strong> — electronic messages<br>📱 <strong>Social Media</strong> — platforms for sharing (Facebook, Twitter, TikTok)<br>🔒 <strong>Cybersecurity</strong> — protecting yourself online</div><h3>Staying Safe Online</h3><div class="example-box">✅ Use strong passwords\n✅ Don't share personal information\n✅ Tell a trusted adult if something feels wrong</div>` }],
    lower: [{ id:'comp-l1', title:'Introduction to Programming', time:'35 min', points:50, body:`<h3>What is a Program?</h3><p>A program is a set of instructions that tells a computer what to do. We write programs using <strong>programming languages</strong>.</p><div class="highlight-box">🐍 <strong>Python</strong> — great for beginners, used in AI and data science<br>🌐 <strong>HTML/CSS</strong> — builds websites<br>☕ <strong>Java</strong> — used for Android apps<br>📱 <strong>JavaScript</strong> — makes websites interactive</div><div class="example-box">Python "Hello World":\nprint("Hello, Africa! 🌍")</div>` }],
    upper: [{ id:'comp-u1', title:'Databases and SQL', time:'40 min', points:50, body:`<h3>What is a Database?</h3><p>A database is an organised collection of data. SQL (Structured Query Language) is used to manage relational databases.</p><div class="example-box">-- Create a table\nCREATE TABLE students (\n  id INT PRIMARY KEY,\n  name VARCHAR(100),\n  grade VARCHAR(20)\n);\n\n-- Query data\nSELECT name, grade FROM students\nWHERE grade = 'Grade 10';</div>` }],
  },

  kiswahili: {
    early: [{ id:'kis-e1', title:'Alfabeti ya Kiswahili', time:'15 min', points:50, body:`<h3>Alfabeti</h3><p>Kiswahili kinatumia alfabeti ya Kilatini. Kuna herufi 24 katika alfabeti ya Kiswahili.</p><div class="highlight-box">🔤 A, B, Ch, D, E, F, G, H, I, J, K, L, M, N, O, P, R, S, T, U, V, W, Y, Z</div><h3>Maneno Rahisi</h3><div class="example-box">Habari → Hello / How are you?\nNzuri → Fine / Good\nAsante → Thank you\nTafadhali → Please\nKaribu → Welcome</div>` }],
    middle: [{ id:'kis-m1', title:'Sarufi: Ngeli za Kiswahili', time:'30 min', points:50, body:`<h3>Ngeli (Noun Classes)</h3><p>Kiswahili kina ngeli nyingi ambazo zinaathiri viambishi vya vitenzi na vivumishi.</p><div class="highlight-box">👤 <strong>M/WA</strong> — watu: mtu/watu, mtoto/watoto<br>🌳 <strong>M/MI</strong> — miti: mti/miti<br>📚 <strong>KI/VI</strong> — vitu: kitu/vitu, kitabu/vitabu<br>🏠 <strong>N/N</strong> — nyumba, ndege, nchi</div>` }],
  },

  agriculture: {
    middle: [{ id:'agr-m1', title:'Soil and Crop Production', time:'30 min', points:50, body:`<h3>Types of Soil</h3><div class="highlight-box">🌱 <strong>Loam</strong> — best for farming; mixture of sand, silt, clay<br>🏖️ <strong>Sandy</strong> — drains fast, poor nutrients<br>🧱 <strong>Clay</strong> — holds water, can become waterlogged<br>⚫ <strong>Black Cotton</strong> — fertile but cracks when dry (common in East Africa)</div><h3>Crop Calendar (East Africa)</h3><div class="example-box">Long Rains (Mar-May): maize, beans, potatoes\nShort Rains (Oct-Dec): sorghum, millet\nIrrigation farming: tomatoes, onions (year-round)</div>` }],
    upper: [{ id:'agr-u1', title:'Modern Farming Technologies', time:'35 min', points:50, body:`<h3>Technology in African Agriculture</h3><div class="highlight-box">🛰️ <strong>Precision Farming</strong> — using GPS and sensors to optimise inputs<br>💧 <strong>Drip Irrigation</strong> — delivers water directly to roots, saves 60% water<br>🌱 <strong>Hydroponics</strong> — growing crops without soil in nutrient solution<br>📱 <strong>AgriTech Apps</strong> — M-Farm, Twiga Foods connect farmers to markets</div>` }],
  },
};

// Flatten all lessons for quiz generation
function getAllLessonsForStudent(student) {
  const level = getGradeLevel(student.grade || 'Grade 7');
  const allLessons = [];
  Object.keys(LESSONS).forEach(subj => {
    const subjLessons = LESSONS[subj];
    const levelKey = ['early','middle','lower','upper'].find(l => subjLessons[l]) || 'middle';
    const actual = subjLessons[level] || subjLessons[levelKey] || [];
    actual.forEach(l => allLessons.push({ ...l, subject: subj }));
  });
  return allLessons;
}

// ── QUIZ QUESTIONS BY SUBJECT & GRADE ───────────────────────────
const QUIZ_QUESTIONS = {
  math: {
    early:  [
      { q:'What is 3 + 4?', opts:['5','6','7','8'], ans:2, explain:'3 + 4 = 7. Count on from 3: four, five, six, seven!' },
      { q:'What shape has 3 sides?', opts:['Circle','Square','Triangle','Rectangle'], ans:2, explain:'A triangle has exactly 3 sides and 3 corners.' },
      { q:'Which number comes after 9?', opts:['8','10','11','12'], ans:1, explain:'After 9 comes 10. We move to a new "ten".' },
      { q:'What is 10 - 4?', opts:['4','5','6','7'], ans:2, explain:'10 - 4 = 6. Count back 4 from 10.' },
      { q:'How many sides does a square have?', opts:['3','4','5','6'], ans:1, explain:'A square has 4 equal sides.' },
    ],
    middle: [
      { q:'What is 3/4 + 1/4?', opts:['1/2','1','4/4','3/8'], ans:1, explain:'Same denominators: add numerators → 3+1=4, so 4/4 = 1.' },
      { q:'Solve: x + 7 = 15', opts:['x=7','x=8','x=9','x=22'], ans:1, explain:'x = 15 - 7 = 8.' },
      { q:'What is 25% of 200?', opts:['25','50','75','100'], ans:1, explain:'25% = 1/4. 200 ÷ 4 = 50.' },
      { q:'What is the area of a rectangle 6cm by 4cm?', opts:['10 cm²','20 cm²','24 cm²','48 cm²'], ans:2, explain:'Area = length × width = 6 × 4 = 24 cm².' },
      { q:'A ratio is 2:3. If the first part is 10, what is the second?', opts:['12','15','20','6'], ans:1, explain:'Scale factor: 10÷2=5. Second part: 3×5=15.' },
    ],
    lower: [
      { q:'Solve: 2x + 3 = 11', opts:['x=3','x=4','x=5','x=7'], ans:1, explain:'2x = 11-3 = 8, x = 4.' },
      { q:'What is the sum of angles in a triangle?', opts:['90°','180°','270°','360°'], ans:1, explain:'All triangles have interior angles summing to 180°.' },
      { q:'Simplify: 3x + 2y - x + 4y', opts:['2x+6y','4x+6y','2x+2y','3x+4y'], ans:0, explain:'Collect like terms: (3x-x) + (2y+4y) = 2x + 6y.' },
      { q:'The gradient of y = 3x + 5 is:', opts:['5','3','8','1/3'], ans:1, explain:'In y = mx + c, m is the gradient. Here m = 3.' },
      { q:'What is 5! (5 factorial)?', opts:['25','60','120','720'], ans:2, explain:'5! = 5×4×3×2×1 = 120.' },
    ],
    upper: [
      { q:'Solve x² - 5x + 6 = 0', opts:['x=2 or x=3','x=-2 or x=-3','x=1 or x=6','x=5 or x=1'], ans:0, explain:'Factorise: (x-2)(x-3)=0, so x=2 or x=3.' },
      { q:'d/dx(4x³) = ?', opts:['4x²','12x²','12x³','3x²'], ans:1, explain:'Power rule: d/dx(xⁿ)=nxⁿ⁻¹. So 4×3×x² = 12x².' },
      { q:'The discriminant of ax²+bx+c is:', opts:['b²-4ac','b+4ac','-b/2a','√(b²-4ac)'], ans:0, explain:'Discriminant = b²-4ac. Determines nature of roots.' },
      { q:'log₁₀(1000) = ?', opts:['2','3','10','100'], ans:1, explain:'10³ = 1000, so log₁₀(1000) = 3.' },
      { q:'∫2x dx = ?', opts:['x²+C','2x²+C','x+C','2+C'], ans:0, explain:'∫2x dx = 2×(x²/2)+C = x²+C.' },
    ],
  },
  biology: {
    lower: [
      { q:'Which organelle is called the "powerhouse" of the cell?', opts:['Nucleus','Ribosome','Mitochondria','Chloroplast'], ans:2, explain:'Mitochondria produce ATP (energy) through cellular respiration.' },
      { q:'Photosynthesis produces:', opts:['CO₂ and water','Glucose and oxygen','ATP only','Proteins'], ans:1, explain:'Photosynthesis: CO₂ + H₂O + light → glucose + O₂.' },
      { q:'DNA stands for:', opts:['Deoxyribose Nucleotide Acid','Deoxyribonucleic Acid','Double Nitrogen Acid','Diribose Acid'], ans:1, explain:'DNA = Deoxyribonucleic Acid — the molecule carrying genetic information.' },
    ],
    upper: [
      { q:'What is the base pairing rule in DNA?', opts:['A-G, T-C','A-T, G-C','A-C, T-G','All bases pair with each other'], ans:1, explain:'Adenine pairs with Thymine; Guanine pairs with Cytosine.' },
      { q:'Which type of mutation causes sickle cell anaemia?', opts:['Deletion','Insertion','Point mutation (substitution)','Translocation'], ans:2, explain:'A single nucleotide substitution changes glutamic acid to valine in haemoglobin.' },
    ],
  },
  chemistry: {
    middle: [
      { q:'Water is made of:', opts:['Hydrogen only','Oxygen only','Hydrogen and Oxygen','Carbon and Oxygen'], ans:2, explain:'Water = H₂O — two hydrogen atoms bonded to one oxygen atom.' },
      { q:'What is the chemical symbol for Gold?', opts:['Go','Gd','Au','Ag'], ans:2, explain:'Au comes from the Latin word "Aurum" meaning gold.' },
    ],
    upper: [
      { q:'In OIL RIG, what does OIL mean?', opts:['Oxidation Is Loss','Oxygen Is Lost','Oxidation Increases Loss','Only In Labs'], ans:0, explain:'OIL RIG: Oxidation Is Loss of electrons, Reduction Is Gain.' },
      { q:'The pH of a neutral solution is:', opts:['0','7','14','1'], ans:1, explain:'pH 7 is neutral. Below 7 = acidic, above 7 = alkaline.' },
    ],
  },
  economics: {
    upper: [
      { q:'What happens to demand when price increases?', opts:['Increases','Decreases','Stays same','Doubles'], ans:1, explain:'Law of Demand: higher price → lower quantity demanded (inverse relationship).' },
      { q:"What is 'opportunity cost'?", opts:['The cost of production','The next best alternative foregone','The price of goods','Tax on goods'], ans:1, explain:'Opportunity cost = the value of the best alternative you gave up to make a choice.' },
      { q:'A market with one seller is called:', opts:['Oligopoly','Monopoly','Perfect competition','Duopoly'], ans:1, explain:'Monopoly = one seller controls the entire market.' },
    ],
  },
  accounting: {
    upper: [
      { q:'The accounting equation is:', opts:['Assets = Revenue - Expenses','Assets = Liabilities + Equity','Revenue = Assets + Liabilities','Profit = Revenue × Costs'], ans:1, explain:'The fundamental accounting equation: Assets = Liabilities + Owner\'s Equity.' },
      { q:'Depreciation is:', opts:['An increase in asset value','A decrease in asset value over time','Cash paid to suppliers','Income earned'], ans:1, explain:'Depreciation records the reduction in value of fixed assets over their useful life.' },
    ],
  },
  english: {
    early: [
      { q:'Which is a vowel?', opts:['B','C','E','D'], ans:2, explain:'Vowels are A, E, I, O, U. E is a vowel.' },
      { q:'"The dog runs fast." What is the verb?', opts:['dog','fast','the','runs'], ans:3, explain:'A verb is an action word. "Runs" is the action in this sentence.' },
    ],
    middle: [
      { q:'An adjective describes a:', opts:['Verb','Noun','Adverb','Conjunction'], ans:1, explain:'Adjectives describe or modify nouns. E.g., "The tall girl" — "tall" is the adjective.' },
      { q:'Which sentence is correct?', opts:['She don\'t like mangoes.','She doesn\'t like mangoes.','She not like mangoes.','She likes not mangoes.'], ans:1, explain:'With "she/he/it" in present simple, use "doesn\'t" (does not) for negatives.' },
    ],
    upper: [
      { q:'What literary device is "The wind screamed"?', opts:['Metaphor','Simile','Personification','Alliteration'], ans:2, explain:'Personification gives human qualities to non-human things. Wind cannot actually scream.' },
      { q:'A Shakespearean sonnet has:', opts:['10 lines','12 lines','14 lines','16 lines'], ans:2, explain:'A sonnet has 14 lines. Shakespearean sonnets: 3 quatrains + 1 couplet, rhyming ABAB CDCD EFEF GG.' },
    ],
  },
  history: {
    middle: [
      { q:'Who led Ghana to independence in 1957?', opts:['Nelson Mandela','Kwame Nkrumah','Jomo Kenyatta','Julius Nyerere'], ans:1, explain:'Kwame Nkrumah led Ghana to become the first sub-Saharan African country to gain independence.' },
      { q:'The slave trade lasted approximately:', opts:['50 years','100 years','400 years','1000 years'], ans:2, explain:'The transatlantic slave trade lasted roughly 400 years (1400s–1800s), with over 12 million Africans enslaved.' },
    ],
    upper: [
      { q:'Apartheid ended in South Africa in:', opts:['1980','1990','1994','2000'], ans:2, explain:'South Africa held its first democratic elections in April 1994, ending apartheid. Nelson Mandela became president.' },
      { q:'The Organisation of African Unity (OAU) was founded in:', opts:['1957','1963','1970','1980'], ans:1, explain:'The OAU was founded in Addis Ababa, Ethiopia on 25 May 1963. It became the African Union in 2002.' },
    ],
  },
  geography: {
    middle: [
      { q:"Africa's highest mountain is:", opts:['Mount Kenya','Mount Kilimanjaro','Mount Elgon','Drakensberg'], ans:1, explain:'Mount Kilimanjaro (5,895m) in Tanzania is Africa\'s highest peak.' },
      { q:"The world's longest river is:", opts:['Amazon','Congo','Niger','Nile'], ans:3, explain:'The Nile River (6,650km) flows through 11 African countries and is the world\'s longest river.' },
    ],
    upper: [
      { q:'Which African country has the largest population?', opts:['Ethiopia','DRC Congo','South Africa','Nigeria'], ans:3, explain:'Nigeria has over 220 million people — the most populous country in Africa.' },
      { q:'The Great Rift Valley runs through:', opts:['West Africa','North Africa','East Africa','Southern Africa'], ans:2, explain:'The East African Rift Valley stretches 6,000km from Ethiopia through Kenya, Tanzania to Mozambique.' },
    ],
  },
};

function getQuizQuestionsForStudent(student, subjectId, count) {
  const level = getGradeLevel(student.grade || 'Grade 7');
  const subjectQs = QUIZ_QUESTIONS[subjectId] || QUIZ_QUESTIONS.math;
  const levelOrder = ['early','middle','lower','upper'];
  let questions = subjectQs[level] || [];
  if (questions.length === 0) {
    // fallback to nearest level
    for (const l of levelOrder) {
      if (subjectQs[l] && subjectQs[l].length > 0) { questions = subjectQs[l]; break; }
    }
  }
  // Also grab from other subjects
  if (questions.length < count) {
    Object.keys(QUIZ_QUESTIONS).forEach(s => {
      if (s !== subjectId) {
        const extra = QUIZ_QUESTIONS[s][level] || QUIZ_QUESTIONS[s].middle || QUIZ_QUESTIONS[s].lower || [];
        questions = [...questions, ...extra];
      }
    });
  }
  const shuffled = [...questions].sort(() => Math.random() - 0.5);
  return shuffled.slice(0, count);
}

// ── AI SYSTEM PROMPT BUILDER ─────────────────────────────────────
function buildSystemPrompt(student) {
  const country = COUNTRY_NAMES[student.country] || 'Africa';
  const curriculum = COUNTRY_CURRICULUM[student.country] || {};
  const flag = COUNTRY_FLAGS[student.country] || '🌍';
  const gradeLevel = getGradeLevel(student.grade || 'Grade 7');

  return `You are EduStar AI Tutor — a warm, encouraging, expert educational assistant built specifically for African students.

STUDENT PROFILE:
- Name: ${student.name}
- Country: ${flag} ${country}
- Grade: ${student.grade}
- Curriculum system: ${curriculum.system || 'African national curriculum'}
- Key exams: ${(curriculum.keyExams || []).join(', ')}
- Grade level category: ${gradeLevel} (early/middle/lower/upper secondary)

YOUR TEACHING APPROACH:
1. Always tailor your explanations to ${student.grade} level — not too complex, not too simple
2. Use examples from ${country} and African contexts (local currencies, geography, culture, food, sports)
3. For ${country}: reference the ${curriculum.system || 'national curriculum'} when relevant
4. Be warm, encouraging, and patient. Celebrate effort and progress
5. Use the Socratic method — ask guiding questions to help students discover answers
6. Break complex topics into small steps. Use analogies to everyday African life
7. Format answers clearly: use numbered steps, bullet points, or tables when helpful
8. If a student is stuck, offer multiple explanations and approaches
9. Correct mistakes gently and explain WHY the answer was wrong
10. Reference real African scientists, leaders, and innovators as examples

CURRICULUM KNOWLEDGE:
- You know the ${country} ${curriculum.system || 'curriculum'} in detail
- You can explain any topic from Grade 1 through Grade 12/13 at the appropriate level
- You know African history, geography, literature, and context deeply
- You understand exam techniques for ${(curriculum.keyExams || ['national exams']).join(', ')}

SUBJECTS YOU TEACH: Mathematics, English, Science, Biology, Chemistry, Physics, History, Geography, Economics, Accounting, Commerce, Business Studies, Computer Studies, Literature, Religious Studies, Civics, Kiswahili, French, Agriculture, and more.

Always respond in a clear, structured, educational way. Keep responses concise but complete. Use emojis sparingly to keep the tone friendly without being distracting.`;
}

// ── TOAST UTILITY ────────────────────────────────────────────────
function toast(msg, type = 'success') {
  let container = document.getElementById('toasts');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    container.id = 'toasts';
    document.body.appendChild(container);
  }
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = msg;
  container.appendChild(el);
  setTimeout(() => el.remove(), 4500);
}

// ── NAV BUILDER ──────────────────────────────────────────────────
function buildNav(student, activePage) {
  const flag = COUNTRY_FLAGS[student.country] || '🌍';
  const country = COUNTRY_NAMES[student.country] || '';
  return `
  <nav>
    <a href="dashboard.html" class="logo">EduStar<span> AI</span></a>
    <div class="nav-right">
      <div class="nav-pill">${flag} ${country} · ${student.grade}</div>
      <div class="nav-pill">⭐ <span id="nav-points">${student.points || 0}</span> pts</div>
      <a href="subjects.html" class="nav-btn${activePage==='subjects'?' primary':''}">📚 Subjects</a>
      <a href="books.html" class="nav-btn${activePage==='books'?' primary':''}">📖 Books</a>
      <a href="quiz.html" class="nav-btn${activePage==='quiz'?' primary':''}">🧠 Quiz</a>
      <div class="student-badge">
        <div class="level-dot" id="nav-level">${student.level || 1}</div>
        <span>${student.name.split(' ')[0]}</span>
      </div>
      <button class="nav-btn" onclick="logout()">↩ Logout</button>
    </div>
  </nav>`;
}
